<?php

namespace ALajusticia\Logins\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => filled($this->label) ? $this->label : null,
            'device_type' => $this->device_type,
            'device' => $this->device,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'ip_address' => $this->ip_address,
            'last_active' => $this->last_active,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_current' => $this->is_current,
        ];
    }
}
