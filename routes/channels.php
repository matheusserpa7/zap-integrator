<?php

use App\Broadcasting\WorkspaceChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('workspaces.{workspacePublicId}', WorkspaceChannel::class);
