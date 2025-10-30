<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeController extends Controller
{   
    // Create
    public function store(Request $request)
    {
        $request->validate([
            'nomor' => 'required|unique:employees,nomor',
            'nama' => 'required',
            'photo' => 'nullable|image|max:2048',
        ]);

        $url = null;
        if ($request->hasFile('photo')) {
            // Upload ke S3
            $path = $request->file('photo')->store('employees', 's3');
            $url = Storage::disk('s3')->url($path);
        }

        $employee = Employee::create([
            'nomor' => $request->nomor,
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'tanggal_lahir' => $request->tanggal_lahir,
            'photo_upload_path' => $url,
            'created_on' => Carbon::now(),
            'created_by' => 'system',
        ]);

        // Simpan ke Redis
        Redis::set('emp_' . $employee->nomor, $employee->toJson());

        return response()->json($employee);
    }

    // Read
    public function show($nomor)
    {
        $cacheKey = 'emp_' . $nomor;
        $cached = Redis::get($cacheKey);

        if ($cached) {
            return response()->json(json_decode($cached));
        }

        $employee = Employee::where('nomor', $nomor)->firstOrFail();

        // Simpan ke Redis
        Redis::set($cacheKey, $employee->toJson());

        return response()->json($employee);
    }

    // Update
    public function update(Request $request, $nomor)
    {
        $employee = Employee::where('nomor', $nomor)->firstOrFail();

        $url = $employee->photo_upload_path;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees', 's3');
            $url = Storage::disk('s3')->url($path);
        }

        $employee->update([
            'nama' => $request->nama ?? $employee->nama,
            'jabatan' => $request->jabatan ?? $employee->jabatan,
            'tanggal_lahir' => $request->tanggal_lahir ?? $employee->tanggal_lahir,
            'photo_upload_path' => $url,
            'updated_on' => Carbon::now(),
            'updated_by' => 'system',
        ]);

        // Update Redis
        Redis::set('emp_' . $employee->nomor, $employee->toJson());

        return response()->json($employee);
    }

    // Delete
    public function destroy($nomor)
    {
        $employee = Employee::where('nomor', $nomor)->firstOrFail();
        $employee->update([
            'deleted_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => Carbon::now(),
            'updated_by' => 'system',
        ]);

        // Hapus cache Redis
        Redis::del('emp_' . $nomor);

        return response()->json(['message' => 'Employee deleted']);
    }
}
