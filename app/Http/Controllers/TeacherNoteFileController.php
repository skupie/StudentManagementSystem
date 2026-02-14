<?php

namespace App\Http\Controllers;

use App\Models\TeacherNote;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class TeacherNoteFileController extends Controller
{
    public function __invoke(TeacherNote $teacherNote)
    {
        $disk = Storage::disk('public');
        $path = $teacherNote->file_path;

        if (! $path || ! $disk->exists($path)) {
            abort(404, 'File not found.');
        }

        $fullPath = $disk->path($path);

        return Response::file($fullPath, [
            'Content-Type' => $teacherNote->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($teacherNote->original_name ?: basename($path)) . '"',
        ]);
    }
}

