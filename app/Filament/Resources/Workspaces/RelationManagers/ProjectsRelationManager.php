<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $relatedResource = ProjectResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
