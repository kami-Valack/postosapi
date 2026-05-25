<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncidentService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>|null  $photos
     */
    public function create(Post $post, User $reporter, array $data, ?array $photos = null): Incident
    {
        $this->validateEquipmentAssociation($post, $data);

        return DB::transaction(function () use ($post, $reporter, $data, $photos) {
            $incident = Incident::query()->create([
                'post_id' => $post->id,
                'user_id' => $reporter->id,
                'category' => $data['category'],
                'equipment_type' => $data['equipment_type'],
                'service_id' => $data['service_id'] ?? null,
                'fuel_type_id' => $data['fuel_type_id'] ?? null,
                'title' => $data['title'] ?? null,
                'description' => $data['description'],
                'status' => 'aberto',
            ]);

            if ($photos) {
                $this->storePhotos($incident, $photos);
            }

            AuditLog::record(
                $reporter->id,
                'gestor.incident.create',
                Incident::class,
                $incident->id,
                null,
                ['category' => $incident->category, 'equipment_type' => $incident->equipment_type]
            );

            return $incident->load(['photos', 'service', 'fuelType', 'reporter:id,name,email']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(Incident $incident, User $admin, array $data): Incident
    {
        $before = ['status' => $incident->status, 'admin_notes' => $incident->admin_notes];

        $incident->status = $data['status'];
        if (array_key_exists('admin_notes', $data)) {
            $incident->admin_notes = $data['admin_notes'];
        }

        if ($data['status'] === 'resolvido') {
            $incident->resolved_at = now();
            $incident->resolved_by = $admin->id;
        } elseif (in_array($data['status'], ['aberto', 'em_andamento'], true)) {
            $incident->resolved_at = null;
            $incident->resolved_by = null;
        }

        $incident->save();

        AuditLog::record(
            $admin->id,
            'admin.incident.update_status',
            Incident::class,
            $incident->id,
            $before,
            ['status' => $incident->status, 'admin_notes' => $incident->admin_notes]
        );

        return $incident->load(['photos', 'post', 'reporter:id,name', 'resolver:id,name']);
    }

    /**
     * @return Collection<int, Incident>
     */
    public function listForPost(Post $post, ?string $status = null): Collection
    {
        $query = Incident::query()
            ->where('post_id', $post->id)
            ->with(['photos', 'service:id,name', 'fuelType:id,name,slug', 'reporter:id,name'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * @param  list<UploadedFile>  $photos
     */
    private function storePhotos(Incident $incident, array $photos): void
    {
        $max = (int) config('incidents.photos.max_files', 5);

        if (count($photos) > $max) {
            throw ValidationException::withMessages([
                'photos' => ["Máximo de {$max} fotos por incidente."],
            ]);
        }

        foreach ($photos as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("incidents/{$incident->id}", 'public');

            IncidentPhoto::query()->create([
                'incident_id' => $incident->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateEquipmentAssociation(Post $post, array $data): void
    {
        $type = $data['equipment_type'] ?? '';

        if ($type === 'servico' && empty($data['service_id'])) {
            throw ValidationException::withMessages([
                'service_id' => ['Indique o serviço associado ao incidente.'],
            ]);
        }

        if (in_array($type, ['bomba', 'ev_charger'], true) && empty($data['fuel_type_id']) && $type === 'bomba') {
            // bomba can use fuel_type_id optionally - not required
        }

        if ($type === 'servico' && ! empty($data['service_id'])) {
            $attached = $post->services()->where('services.id', $data['service_id'])->exists();
            if (! $attached) {
                throw ValidationException::withMessages([
                    'service_id' => ['O serviço não está associado a este posto.'],
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Incident $incident): array
    {
        $incident->loadMissing(['photos', 'service', 'fuelType', 'reporter', 'post:id,name']);

        return [
            'id' => $incident->id,
            'post_id' => $incident->post_id,
            'post_nome' => $incident->post?->name,
            'category' => $incident->category,
            'equipment_type' => $incident->equipment_type,
            'service' => $incident->service ? ['id' => $incident->service->id, 'name' => $incident->service->name] : null,
            'combustivel' => $incident->fuelType ? [
                'id' => $incident->fuelType->id,
                'slug' => $incident->fuelType->slug,
                'name' => $incident->fuelType->name,
            ] : null,
            'title' => $incident->title,
            'description' => $incident->description,
            'status' => $incident->status,
            'admin_notes' => $incident->admin_notes,
            'reported_by' => $incident->reporter ? [
                'id' => $incident->reporter->id,
                'name' => $incident->reporter->name,
            ] : null,
            'photos' => $incident->photos->map(fn (IncidentPhoto $p) => [
                'id' => $p->id,
                'url' => $p->url(),
                'original_name' => $p->original_name,
            ])->values()->all(),
            'created_at' => $incident->created_at?->toIso8601String(),
            'resolved_at' => $incident->resolved_at?->toIso8601String(),
        ];
    }
}
