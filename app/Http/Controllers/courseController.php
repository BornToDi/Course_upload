<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Include Auth for user identification

class CourseController extends Controller
{
    public function courseUpload(Request $req)
    {
        // Validate incoming request
        $req->validate([
            'c_title' => 'required',
            'c_category' => 'required',
            'c_vidFile' => 'required|mimes:mp4,MOV,WMV,AVI,AVCHD,MKV|max:500000',
            'c_thumbFile' => 'required|mimes:jpeg,jpg,png,gif|max:2048',
            'c_docfile' => 'nullable|mimes:csv,txt,xlx,xlsx,doc,docx,zip,xls,pdf|max:2048',
            'c_description' => 'required'
        ]);

        // Create new course instance
        $courseModel = new Course;

        // Fill course attributes
        $courseModel->c_title = $req->c_title;
        $courseModel->c_category = $req->c_category;
        $courseModel->c_description = $req->c_description;
        $courseModel->uploaded_by = Auth::id(); // Store authenticated user ID

        // Handle video upload
        if ($req->hasFile('c_vidFile')) {
            $fileName = time() . '_' . $req->c_vidFile->getClientOriginalName();
            $filePath = $req->file('c_vidFile')->storeAs('vids_uploads', $fileName, 'public');
            $courseModel->c_vid_file_name = $fileName;
            $courseModel->c_vid_file_path = '/storage/' . $filePath;
        }

        // Handle thumbnail upload
        if ($req->hasFile('c_thumbFile')) {
            $fileName = time() . '_' . $req->c_thumbFile->getClientOriginalName();
            $filePath = $req->file('c_thumbFile')->storeAs('thumbs_uploads', $fileName, 'public');
            $courseModel->c_thumb_file_name = $fileName;
            $courseModel->c_thumb_file_path = '/storage/' . $filePath;
        }

        // Handle additional document upload
        if ($req->hasFile('c_docfile')) {
            $fileName = time() . '_' . $req->c_docfile->getClientOriginalName();
            $filePath = $req->file('c_docfile')->storeAs('doc_uploads', $fileName, 'public');
            $courseModel->c_adoc_file_name = $fileName;
            $courseModel->c_adoc_file_path = '/storage/' . $filePath;
        }

        // Save course data to the database
        $courseModel->save();

        return response()->json(['success' => 'Successfully uploaded course']);
    }

    public function fileDownload($file)
    {
        return response()->download(storage_path('app/public/vids_uploads/' . $file));
    }

    public function getRecords(Request $request)
    {
        $filedata = Course::all();

        return view('Instructor_module/home_page', [
            'filedata' => $filedata
        ]);
    }

    public function getRecordsMC(Request $request)
    {
        $coursedata = Course::all();

        if ($request->ajax()) {
            return response()->json([
                'coursedata' => $coursedata,
            ]);
        }

        return view('manage_course', [
            'coursedata' => $coursedata
        ]);
    }

    public function courseView($id)
    {
        $courseView = Course::find($id);

        return response()->json([
            'data' => $courseView
        ]);
    }

    public function updateCourse(Request $req, $id)
    {
        $req->validate([
            'c_title' => 'required',
            'c_category' => 'required',
            'c_description' => 'required'
        ]);

        $courseId = Course::find($id);

        if ($courseId) {
            $courseId->c_title = $req->c_title;
            $courseId->c_category = $req->c_category;
            $courseId->c_description = $req->c_description;

            // Handle video upload if a new file is provided
            if ($req->hasFile('c_vidFile')) {
                $this->deleteFile('vids_uploads', $courseId->c_vid_file_name);
                $fileName = time() . '_' . $req->c_vidFile->getClientOriginalName();
                $filePath = $req->file('c_vidFile')->storeAs('vids_uploads', $fileName, 'public');
                $courseId->c_vid_file_name = $fileName;
                $courseId->c_vid_file_path = '/storage/' . $filePath;
            }

            // Handle thumbnail upload if a new file is provided
            if ($req->hasFile('c_thumbFile')) {
                $this->deleteFile('thumbs_uploads', $courseId->c_thumb_file_name);
                $fileName = time() . '_' . $req->c_thumbFile->getClientOriginalName();
                $filePath = $req->file('c_thumbFile')->storeAs('thumbs_uploads', $fileName, 'public');
                $courseId->c_thumb_file_name = $fileName;
                $courseId->c_thumb_file_path = '/storage/' . $filePath;
            }

            // Handle additional document upload if a new file is provided
            if ($req->hasFile('c_docfile')) {
                $this->deleteFile('doc_uploads', $courseId->c_adoc_file_name);
                $fileName = time() . '_' . $req->c_docfile->getClientOriginalName();
                $filePath = $req->file('c_docfile')->storeAs('doc_uploads', $fileName, 'public');
                $courseId->c_adoc_file_name = $fileName;
                $courseId->c_adoc_file_path = '/storage/' . $filePath;
            }

            $courseId->save();
            return response()->json(['success' => true, 'message' => 'Course Updated Successfully']);
        } else {
            return response()->json(['success' => false, 'message' => 'ID Not Found']);
        }
    }

    public function destroyCourse($id)
    {
        $courseDel = Course::find($id);
        if ($courseDel) {
            $this->deleteFile('vids_uploads', $courseDel->c_vid_file_name);
            $this->deleteFile('thumbs_uploads', $courseDel->c_thumb_file_name);
            $this->deleteFile('doc_uploads', $courseDel->c_adoc_file_name);
            $courseDel->delete();
            return response()->json(['success' => true, 'message' => 'Course Deleted Successfully']);
        } else {
            return response()->json(['success' => false, 'message' => 'Course Not Found']);
        }
    }

    private function deleteFile($folder, $fileName)
    {
        $filePath = storage_path('app/public/' . $folder . '/' . $fileName);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
