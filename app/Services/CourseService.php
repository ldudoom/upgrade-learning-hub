<?php

namespace App\Services;

use App\Models\Course;
use Exception;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * Crea un nuevo curso con manejo de transacciones para PostgreSQL.
     */
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            try {
                return Course::create([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'status' => $data['status'] ?? 'draft',
                ]);
            } catch (Exception $e) {
                // Aquí podrías loguear el error específico de Postgres
                throw new Exception('Error al crear el curso: '.$e->getMessage());
            }
        });
    }

    public function getPublishedCourses(string $search = '')
    {
        return \App\Models\Course::query()
            ->where('status', 'published')
            ->when($search, function ($query, $search) {
                return $query->where('title', 'ilike', "%{$search}%"); // 'ilike' es ideal para Postgres (case-insensitive)
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
