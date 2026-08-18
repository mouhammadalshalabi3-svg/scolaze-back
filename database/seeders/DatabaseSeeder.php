<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Admin;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\TeachingAssignment;
use App\Models\Supervisor;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\StudentEvaluation;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\Announcement;
use App\Models\Period;
use App\Models\PeriodAttendance;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Salary;
use App\Models\Homework;
use App\Models\HomeworkGrade;
use App\Models\AdminMessage;
use App\Models\AbsenceJustification;
use App\Models\StaffAttendance;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────
        // 1) الأدمن
        // ─────────────────────────────────────────
        $adminUser = User::create([
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'role'     => 'admin',
            'status'   => true,
        ]);

        Admin::create([
            'fullname' => 'علي جل',
            'phone'    => '+963900000001',
            'email'    => 'admin@scolaze.test',
            'user_id'  => $adminUser->id,
        ]);

        // ─────────────────────────────────────────
        // 2) السنة الدراسية
        // ─────────────────────────────────────────
        $year = AcademicYear::create([
            'name'       => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-06-30',
        ]);

        // ─────────────────────────────────────────
        // 3) الصفوف والشعب
        // ─────────────────────────────────────────
        $gradeTenth    = Grade::create(['name' => 'العاشر']);
        $gradeEleventh = Grade::create(['name' => 'الحادي عشر']);

        $sections = [];
        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            foreach (['أ', 'ب'] as $letter) {
                $sections[$grade->id][] = Section::create([
                    'name'             => $grade->name . ' - شعبة ' . $letter,
                    'academic_year_id' => $year->id,
                    'grade_id'         => $grade->id,
                ]);
            }
        }

        // ─────────────────────────────────────────
        // 4) المواد (كل صف نسخته الخاصة)
        // ─────────────────────────────────────────
        $subjectNames = ['Mathematics', 'Physics', 'Arabic', 'English'];
        $subjects = []; // subjects[grade_id][subject_name] = Subject

        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            foreach ($subjectNames as $name) {
                $subjects[$grade->id][$name] = Subject::create([
                    'name'        => $name,
                    'description' => 'مادة ' . $name . ' لـ ' . $grade->name,
                    'grade_id'    => $grade->id,
                ]);
            }
        }

        // ─────────────────────────────────────────
        // 5) الأساتذة (أستاذ لكل مادة، مربوط بشعبتي الصفين للمادة نفسها)
        // ─────────────────────────────────────────
        $teacherNamesBySubject = [
            'Mathematics' => ['أحمد', 'خالد'],
            'Physics'     => ['سارة', 'ريم'],
            'Arabic'      => ['محمد', 'ليلى'],
            'English'     => ['نور', 'يوسف'],
        ];

        $teachers = []; // teachers[subject_name] = [Teacher, Teacher]

        foreach ($teacherNamesBySubject as $subjectName => $names) {
            foreach ($names as $i => $firstName) {
                do {
                    $username = 'tch_' . Str::lower($firstName) . rand(100, 999);
                } while (User::where('username', $username)->exists());

                $tUser = User::create([
                    'username' => $username,
                    'password' => bcrypt('teacher123'),
                    'role'     => 'teacher',
                    'status'   => true,
                ]);

                $teacher = Teacher::create([
                    'first_name'      => $firstName,
                    'last_name'       => 'الأستاذ',
                    'phone'           => '+96393000' . rand(1000, 9999),
                    'email'           => null,
                    'salary_type'     => 'per_session',
                    'rate_per_lesson' => rand(15, 30),
                    'subject_id'      => $subjects[$gradeTenth->id][$subjectName]->id, // تصنيف عرضي
                    'user_id'         => $tUser->id,
                ]);

                $teachers[$subjectName][] = $teacher;

                // ربط الأستاذ بكلا نسخي المادة (عاشر وحادي عشر)
                foreach ([$gradeTenth, $gradeEleventh] as $grade) {
                    TeacherSubject::create([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subjects[$grade->id][$subjectName]->id,
                    ]);
                }
            }
        }

        // تعيين: الأستاذ الأول من كل مادة يدرّس شعبة "أ" بالصفين، والتاني يدرّس شعبة "ب"
        $assignments = []; // لتخزين TeachingAssignment مشان نبني عليها Periods

        foreach ($subjectNames as $subjectName) {
            foreach ([$gradeTenth, $gradeEleventh] as $grade) {
                foreach ($sections[$grade->id] as $index => $section) {
                    $teacher = $teachers[$subjectName][$index]; // أ = index0, ب=index1
                    $assignments[] = TeachingAssignment::create([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subjects[$grade->id][$subjectName]->id,
                        'section_id' => $section->id,
                    ]);
                }
            }
        }

        // ─────────────────────────────────────────
        // 6) المشرفين (وحد لكل صف)
        // ─────────────────────────────────────────
        $supervisorsByGrade = [];
        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            do {
                $username = 'sup_' . Str::lower(explode(' ', $grade->name)[0]) . rand(100, 999);
            } while (User::where('username', $username)->exists());

            $sUser = User::create([
                'username' => $username,
                'password' => bcrypt('supervisor123'),
                'role'     => 'supervisor',
                'status'   => true,
            ]);

            $supervisorsByGrade[$grade->id] = Supervisor::create([
                'first_name'     => 'مشرف',
                'last_name'      => $grade->name,
                'phone'          => '+96391000' . rand(1000, 9999),
                'email'          => null,
                'monthly_salary' => 600,
                'grade_id'       => $grade->id,
                'photo'          => null,
                'user_id'        => $sUser->id,
            ]);
        }

        // ─────────────────────────────────────────
        // 7) الطلاب + الأهل (15 طالب بكل شعبة)
        // ─────────────────────────────────────────
        $firstNames = ['يوسف', 'فاطمة', 'عمر', 'ليلى', 'زياد', 'مريم', 'كريم', 'هدى', 'سامي', 'رنا', 'وائل', 'دانا', 'باسل', 'جود', 'رامي'];

        $allStudents = [];

        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            foreach ($sections[$grade->id] as $section) {
                foreach (range(1, 15) as $i) {
                    $fName = $firstNames[($i - 1) % count($firstNames)];

                    do {
                        $stdUsername = 'std_' . Str::lower($fName) . rand(100, 999);
                    } while (User::where('username', $stdUsername)->exists());

                    $stdUser = User::create([
                        'username' => $stdUsername,
                        'password' => bcrypt('student123'),
                        'role'     => 'student',
                        'status'   => true,
                    ]);

                    $parentPassword = 'parent123';
                    $parentUser = User::create([
                        'username' => null,
                        'password' => bcrypt($parentPassword),
                        'role'     => 'parent',
                        'status'   => true,
                    ]);

                    $parent = ParentModel::create([
                        'father_name' => 'والد ' . $fName,
                        'mother_name' => 'والدة ' . $fName,
                        'phone'       => '+96394' . rand(1000000, 9999999),
                        'email'       => null,
                        'user_id'     => $parentUser->id,
                    ]);

                    do {
                        $academicNumber = 'STD' . date('Y') . rand(1000, 9999);
                    } while (Student::where('academic_number', $academicNumber)->exists());

                    $student = Student::create([
                        'academic_number' => $academicNumber,
                        'first_name'      => $fName,
                        'last_name'       => 'الطالب',
                        'birth_date'      => now()->subYears(16)->subDays(rand(1, 300)),
                        'gender'          => rand(0, 1) ? 'male' : 'female',
                        'address'         => 'دمشق - سوريا',
                        'photo'           => null,
                        'user_id'         => $stdUser->id,
                        'parent_id'       => $parent->id,
                        'section_id'      => $section->id,
                    ]);

                    $allStudents[] = ['student' => $student, 'grade' => $grade];
                }
            }
        }

        // ─────────────────────────────────────────
        // 8) مكتبة المواد
        // ─────────────────────────────────────────
        $categories = collect(['ملازم دراسية', 'فيديوهات شرح', 'واجبات وتمارين'])
            ->map(fn($name) => LibraryCategory::create(['name' => $name]));

        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            foreach ($subjectNames as $subjectName) {
                LibraryItem::create([
                    'title'       => 'ملخص ' . $subjectName . ' - ' . $grade->name,
                    'description' => 'ملزمة شاملة لمنهج ' . $subjectName,
                    'file_uri'    => 'library/sample.pdf',
                    'type'        => 'pdf',
                    'category_id' => $categories->random()->id,
                    'grade_id'    => $grade->id,
                    'subject_id'  => $subjects[$grade->id][$subjectName]->id,
                ]);
            }
        }

        // ─────────────────────────────────────────
        // 9) الإعلانات
        // ─────────────────────────────────────────
        Announcement::create([
            'title'       => 'إعلان هام لطلاب الصف العاشر',
            'content'     => 'يرجى من جميع طلاب الصف العاشر إحضار الكتب المدرسية غداً.',
            'target_role' => 'first_grade',
            'type'        => 'important',
            'user_id'     => $adminUser->id,
            'image'       => null,
        ]);

        Announcement::create([
            'title'       => 'تعديل موعد امتحان الفيزياء',
            'content'     => 'تم تأجيل امتحان الفيزياء ليوم الخميس القادم.',
            'target_role' => 'second_grade',
            'type'        => 'urgent',
            'user_id'     => $adminUser->id,
            'image'       => null,
        ]);

        Announcement::create([
            'title'       => 'نشاط رياضي بالمدرسة',
            'content'     => 'ستقام مسابقة كرة قدم بين شعب المدرسة يوم السبت.',
            'target_role' => 'all',
            'type'        => 'activity',
            'user_id'     => $adminUser->id,
            'image'       => null,
        ]);

        Announcement::create([
            'title'       => 'اجتماع الهيئة التدريسية',
            'content'     => 'يرجى من كافة الأساتذة حضور الاجتماع الشهري يوم الأحد الساعة 10 صباحاً.',
            'target_role' => 'teachers',
            'type'        => 'normal',
            'user_id'     => $adminUser->id,
            'image'       => null,
        ]);

        Announcement::create([
            'title'       => 'تحديث السياسة الداخلية للزي المدرسي',
            'content'     => 'يرجى الالتزام بالزي الموحد الجديد بدءاً من الأسبوع القادم.',
            'target_role' => 'all',
            'type'        => 'rule_policy',
            'user_id'     => $adminUser->id,
            'image'       => null,
        ]);

        // ─────────────────────────────────────────
        // 10) الجدول الأسبوعي (Periods) — 6 حصص × 5 أيام لكل شعبة
        // ─────────────────────────────────────────
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

        // نجمع تعيينات كل شعبة لحالها
        $assignmentsBySection = collect($assignments)->groupBy('section_id');

        foreach ($assignmentsBySection as $sectionId => $sectionAssignments) {
            $sectionAssignments = $sectionAssignments->values();
            foreach ($days as $day) {
                foreach (range(1, 6) as $periodNumber) {
                    $assignment = $sectionAssignments[($periodNumber - 1) % $sectionAssignments->count()];

                    Period::create([
                        'period_number' => $periodNumber,
                        'start_time'    => sprintf('%02d:00:00', 7 + $periodNumber),
                        'end_time'      => sprintf('%02d:45:00', 7 + $periodNumber),
                        'day_of_week'   => $day,
                        'status'        => null,
                        'assignment_id' => $assignment->id,
                    ]);
                }
            }
        }

        // ─────────────────────────────────────────
        // 11) حضور الحصص (PeriodAttendance) — آخر 20 يوم دراسي
        // ─────────────────────────────────────────
        $allPeriods = Period::with('assignment.section.grade')->get();

        foreach ($allPeriods as $period) {
            $grade = $period->assignment->section->grade;
            $supervisor = $supervisorsByGrade[$grade->id];

            foreach (range(1, 20) as $dayOffset) {
                $date = now()->subDays($dayOffset);
                $status = collect(['completed', 'completed', 'completed', 'completed', 'absent', 'cancelled'])->random();

                PeriodAttendance::create([
                    'period_id'     => $period->id,
                    'session_date'  => $date->toDateString(),
                    'status'        => $status,
                    'supervisor_id' => $supervisor->id,
                ]);
            }
        }

        // ─────────────────────────────────────────
        // 12) الامتحانات + العلامات
        // ─────────────────────────────────────────
        foreach ([$gradeTenth, $gradeEleventh] as $grade) {
            foreach ($sections[$grade->id] as $section) {
                foreach ($subjectNames as $subjectName) {
                    $subject = $subjects[$grade->id][$subjectName];

                    $exam = Exam::create([
                        'exam_type'  => 'exam_1',
                        'exam_date'  => now()->subDays(10)->toDateString(),
                        'total_mark' => 100,
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                    ]);
                }
            }
        }

        foreach ($allStudents as $entry) {
            $student = $entry['student'];
            $grade   = $entry['grade'];

            foreach ($subjectNames as $subjectName) {
                $subject = $subjects[$grade->id][$subjectName];

                // علامة أساسية
                StudentGrade::create([
                    'student_id'    => $student->id,
                    'subject_id'    => $subject->id,
                    'type'          => 'main',
                    'mark'          => rand(60, 100),
                    'parent_viewed' => (bool) rand(0, 1),
                ]);

                // علامة نشاط
                StudentGrade::create([
                    'student_id'    => $student->id,
                    'subject_id'    => $subject->id,
                    'type'          => 'activity',
                    'mark'          => rand(70, 100),
                    'parent_viewed' => false,
                ]);

                // تقييم سلوكي
                $comments = [
                    'مشاركة ممتازة وانتباه عالي بالصف.',
                    'أداء جيد، يحتاج تحسين بالواجبات.',
                    'التزام تام بالمواعيد والانضباط.',
                    'يحتاج دعم إضافي بالمادة.',
                ];

                StudentEvaluation::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'comment'    => collect($comments)->random(),
                    'grade'      => rand(6, 10),
                ]);
            }

            // حضور الطالب اليومي (آخر 20 يوم)
            $supervisor = $supervisorsByGrade[$grade->id];
            foreach (range(1, 20) as $dayOffset) {
                Attendance::create([
                    'attendance_date' => now()->subDays($dayOffset)->toDateString(),
                    'status'          => collect(['present', 'present', 'present', 'absent', 'late'])->random(),
                    'supervisor_id'   => $supervisor->id,
                    'student_id'      => $student->id,
                ]);
            }
        }

        // ─────────────────────────────────────────
        // 13) الرواتب (شهر حالي، مبنية على sessions تقريبية)
        // ─────────────────────────────────────────
        foreach (collect($teachers)->flatten() as $teacher) {
            $completedSessions = rand(15, 24);
            $rate = $teacher->rate_per_lesson;

            Salary::create([
                'month'              => now()->month,
                'year'               => now()->year,
                'total_amount'       => $completedSessions * $rate,
                'paid_status'        => collect(['pending', 'paid'])->random(),
                'completed_sessions' => $completedSessions,
                'price_per_session'  => $rate,
                'working_days'       => rand(18, 22),
                'user_id'            => $teacher->user_id,
            ]);
        }

        // ─────────────────────────────────────────
        // 14) الواجبات (Homework) + علامات الواجبات (HomeworkGrade)
        // ─────────────────────────────────────────
        $studentsBySection = collect($allStudents)->groupBy(fn($e) => $e['student']->section_id);

        $homeworkTitles = [
            'تمارين الوحدة الأولى',
            'حل أسئلة الكتاب صفحة 20',
            'بحث قصير عن الموضوع المطروح',
            'تحضير الدرس القادم',
        ];

        foreach ($assignments as $assignment) {
            foreach (range(1, 2) as $i) {
                $homework = Homework::create([
                    'title'         => collect($homeworkTitles)->random(),
                    'description'   => 'المطلوب تسليم الواجب قبل الموعد المحدد.',
                    'due_date'      => now()->addDays(rand(2, 10))->toDateString(),
                    'assignment_id' => $assignment->id,
                ]);

                $sectionStudents = $studentsBySection[$assignment->section_id] ?? collect();

                foreach ($sectionStudents as $entry) {
                    // نترك حوالي ثلث الطلاب بلا علامة لسا (واجب لسا ما تصحح)
                    if (rand(1, 3) === 1) {
                        continue;
                    }

                    HomeworkGrade::create([
                        'homework_id' => $homework->id,
                        'student_id'  => $entry['student']->id,
                        'mark'        => rand(60, 100),
                    ]);
                }
            }
        }

        // ─────────────────────────────────────────
        // 15) رسائل الإدارة (AdminMessage) — أدمن↔أستاذ، أدمن↔أهل، أستاذ↔أهل
        // ─────────────────────────────────────────
        $admin        = Admin::where('user_id', $adminUser->id)->first();
        $firstTeacher = collect($teachers)->flatten()->first();
        $firstParent  = $allStudents[0]['student']->parent;

        AdminMessage::create([
            'title'       => 'تذكير باجتماع الهيئة التدريسية',
            'message'     => 'الرجاء الحضور بالموعد المحدد يوم الأحد.',
            'is_read'     => false,
            'admin_id'    => $admin->id,
            'teacher_id'  => $firstTeacher->id,
            'parent_id'   => null,
            'sender_role' => 'admin',
        ]);

        AdminMessage::create([
            'title'       => 'استفسار من أستاذ الرياضيات',
            'message'     => 'هل يمكن تأجيل موعد الاجتماع نصف ساعة؟',
            'is_read'     => false,
            'admin_id'    => $admin->id,
            'teacher_id'  => $firstTeacher->id,
            'parent_id'   => null,
            'sender_role' => 'teacher',
        ]);

        AdminMessage::create([
            'title'       => 'إشعار بخصوص الرسوم المدرسية',
            'message'     => 'يرجى تسديد القسط المتبقي قبل نهاية الشهر.',
            'is_read'     => false,
            'admin_id'    => $admin->id,
            'teacher_id'  => null,
            'parent_id'   => $firstParent->id,
            'sender_role' => 'admin',
        ]);

        AdminMessage::create([
            'title'       => 'ملاحظة حول أداء الطالب',
            'message'     => 'ابنكم بحاجة لمتابعة إضافية بمادة الرياضيات هالفترة.',
            'is_read'     => false,
            'admin_id'    => null,
            'teacher_id'  => $firstTeacher->id,
            'parent_id'   => $firstParent->id,
            'sender_role' => 'teacher',
        ]);

        // ─────────────────────────────────────────
        // 16) تبريرات الغياب (AbsenceJustification) — عالغيابات/التأخيرات الموجودة
        // ─────────────────────────────────────────
        $absentRecords = Attendance::whereIn('status', ['absent', 'late'])
            ->with('student')
            ->inRandomOrder()
            ->take(15)
            ->get();

        $justificationStatuses = ['pending', 'approved', 'rejected'];

        foreach ($absentRecords as $index => $attendance) {
            AbsenceJustification::create([
                'reason'        => 'الطالب كان مريض وتم إحضار تقرير طبي.',
                'status'        => $justificationStatuses[$index % count($justificationStatuses)],
                'attendance_id' => $attendance->id,
                'parent_id'     => $attendance->student->parent_id,
            ]);
        }

        // ─────────────────────────────────────────
        // 17) حضور الطاقم (StaffAttendance) — آخر 10 أيام، تسجيل الأدمن
        // ─────────────────────────────────────────
        $staffUsers = User::whereIn('role', ['admin', 'teacher', 'supervisor'])->get();

        foreach ($staffUsers as $staffUser) {
            foreach (range(1, 10) as $dayOffset) {
                StaffAttendance::create([
                    'attendance_date'      => now()->subDays($dayOffset)->toDateString(),
                    'status'               => collect(['present', 'present', 'present', 'absent', 'late'])->random(),
                    'user_id'              => $staffUser->id,
                    'recorded_by_admin_id' => $admin->id,
                ]);
            }
        }

        $this->command->info('✅ السيدر خلص بنجاح: صفين (عاشر/حادي عشر)، 4 شعب، 60 طالب، 8 أساتذة، مشرفين، مواد، امتحانات، علامات، تقييمات، مكتبة، إعلانات، جداول، حضور، رواتب، واجبات، رسائل، تبريرات غياب، وحضور طاقم.');
    }
}