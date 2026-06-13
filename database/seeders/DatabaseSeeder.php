<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Exam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LibraryCategory;
use App\Models\Supervisor;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1) يوزر الأدمن + صف admins المرتبط
        $adminUser = User::create([
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role'     => 'admin',
            'status'   => true,
        ]);

        Admin::create([
            'fullname' => 'مدير المدرسة',
            'phone'    => '0991111111',
            'email'    => 'admin@test.com',
            'user_id'  => $adminUser->id,
        ]);

        // 2) يوزر الأستاذ + صف teachers المرتبط
        $teacherUser = User::create([
            'username' => 'teacher1',
            'password' => bcrypt('password'),
            'role'     => 'teacher',
            'status'   => true,
        ]);

        Teacher::create([
            'first_name'  => 'محمود',
            'last_name'   => 'أحمد',
            'phone'       => '0991234567',
            'email'       => 'teacher@test.com',
            'salary_type' => 'per_session',
            'photo'       => null,
            'user_id'     => $teacherUser->id,
        ]);

        // 3) يوزر ولي الأمر + صف parents المرتبط
        $parentUser = User::create([
            'username' => 'parent1',
            'password' => bcrypt('password'),
            'role'     => 'parent',
            'status'   => true,
        ]);

        $parent = ParentModel::create([
            'father_name' => 'محمد أحمد',
            'mother_name' => 'فاطمة محمد',
            'phone'       => '0991234567',
            'email'       => 'parent@test.com',
            'user_id'     => $parentUser->id,
        ]);

        // 4) السنة الدراسية + الصف + الشعبة
        $academicYear = AcademicYear::create([
            'name'       => 'السنة الأولى',
            'start_date' => '2024-09-01',
            'end_date'   => '2025-06-30',
        ]);

        $grade = Grade::create(['name' => 'أ']);

        $section = Section::create([
            'name'             => 'الصف الأول أ',
            'academic_year_id' => $academicYear->id,
            'grade_id'         => $grade->id,
        ]);

        // 5) يوزر الطالب + صف students المرتبط
        $studentUser = User::create([
            'username' => 'student1',
            'password' => bcrypt('password'),
            'role'     => 'student',
            'status'   => true,
        ]);

        Student::create([
            'academic_number' => '2024001',
            'first_name'      => 'أحمد',
            'last_name'       => 'محمد',
            'birth_date'      => '2010-01-01',
            'gender'          => 'male',
            'address'         => 'دمشق',
            'photo'           => null,
            'user_id'         => $studentUser->id,
            'parent_id'       => $parent->id,
            'section_id'      => $section->id,
        ]);

        // 6) مادة + امتحان
        $subject = Subject::create([
            'name'        => 'رياضيات',
            'description' => 'مادة الرياضيات',
        ]);

        Exam::create([
            'exam_type'  => 'exam_1',
            'exam_date'  => '2025-01-10',
            'total_mark' => 100,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);

          LibraryCategory::create([
            'name' => 'كتب',
        ]);

        // 8) يوزر مراقب (supervisor) + صف supervisors المرتبط
        $supervisorUser = User::create([
            'username' => 'supervisor1',
            'password' => bcrypt('password'),
            'role'     => 'supervisor',
            'status'   => true,
        ]);

        $supervisor = Supervisor::create([
            'first_name' => 'علي',
            'last_name'  => 'حسن',
            'phone'      => '0991122334',
            'email'      => 'supervisor@test.com',
            'user_id'    => $supervisorUser->id,
        ]);

        // 9) سجل حضور تجريبي
        \App\Models\Attendance::create([
            'attendance_date' => '2025-01-10',
            'status'          => 'present',
            'supervisor_id'   => $supervisor->id,
            'student_id'      => $studentUser ? \App\Models\Student::first()->id : null,
        ]);
    }
}