<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\Subject;

class LibraryController extends Controller
{
    // GET /library/categories
    public function categories()
    {
        return response()->json(['categories' => LibraryCategory::all()]);
    }

    // GET /subjects/{subject_id}/library?type=pdf|video
    public function forSubject(Request $request, $subject_id)
    {
        Subject::findOrFail($subject_id);

        $request->validate([
            'type' => 'nullable|in:pdf,video',
        ]);

        $items = LibraryItem::where('subject_id', $subject_id)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->with('category')
            ->get()
            ->map(fn($i) => [
                'item_id'  => $i->id,
                'title'    => $i->title,
                'type'     => $i->type,
                'file_uri' => $i->file_uri,
                'category' => $i->category?->name,
            ]);

        return response()->json(['items' => $items]);
    }
}
