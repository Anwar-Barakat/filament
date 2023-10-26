<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use GMP;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Employees Management';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->first_name . ' ' . $record->last_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'middle_name', 'last_name', 'country.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Country' => $record->country->name,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['country']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 5 ? 'info' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Relationships')
                    ->description('Fill out the employee country, state, city, and department.')
                    ->schema([
                        Forms\Components\Select::make('country_id')
                            ->required()
                            ->relationship(name: 'country', titleAttribute: 'name')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('state_id', null);
                                $set('city_id', null);
                            })
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('state_id')
                            ->label('State')
                            ->required()
                            ->options(fn (Get $get): Collection => State::query()
                                ->where('country_id', $get('country_id'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('city_id', null))
                            ->native(false),
                        Forms\Components\Select::make('city_id')
                            ->label('City')
                            ->required()
                            ->options(fn (Get $get): Collection => City::query()
                                ->where('state_id', $get('state_id'))
                                ->pluck('name', 'id'))
                            ->live()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('department_id')
                            ->required()
                            ->relationship(name: 'department', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])->columns(2),
                Forms\Components\Section::make('User Name')
                    ->description('Put the username details in.')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),
                Forms\Components\Section::make('User Address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('Dates')->schema([
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->displayFormat('d/m/y')
                        ->required(),
                    Forms\Components\DatePicker::make('date_of_hired')
                        ->displayFormat('d/m/y')
                        ->required(),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name')
                    ->numeric()
                    ->sortable()
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->numeric()
                    ->sortable()
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_hired')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(
                [
                    SelectFilter::make('department_id')
                        ->relationship('department', 'name')->searchable()
                        ->label('Filter by Department')
                        ->preload()
                        ->native(false)
                        ->indicator('department'),
                    Filter::make('created_at')
                        ->form([
                            DatePicker::make('created_from'),
                            DatePicker::make('created_until'),
                        ])->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                                );
                        })->indicateUsing(function (array $data): array {
                            $indicators = [];
                            if ($data['created_from'] ?? null)
                                $indicators['created_from'] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();

                            if ($data['created_until'] ?? null)
                                $indicators['created_until'] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();

                            return $indicators;
                        })
                ]
                // ,layout: FiltersLayout::AboveContent
            )
            // ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(Notification::make()
                        ->info()
                        ->title('Employee deleted')
                        ->body('The employee was deleted successfully'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Relationships')->schema([
                TextEntry::make('country.name')->label('Country Name'),
                TextEntry::make('state.name')->label('State Name'),
                TextEntry::make('city.name')->label('City Name'),
                TextEntry::make('department.name')->label('Department Name'),
            ])->columns(2),
            Section::make('Employee Name')->schema([
                TextEntry::make('first_name'),
                TextEntry::make('middle_name'),
                TextEntry::make('last_name'),
            ])->columns(3),
            Section::make('Employee Address')->schema([
                TextEntry::make('address'),
                TextEntry::make('zip_code'),
            ])->columns(2),
            Section::make('Employee Birth & Hired Dates')->schema([
                TextEntry::make('date_of_birth'),
                TextEntry::make('date_of_hired'),
            ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'     => Pages\ListEmployees::route('/'),
            'create'    => Pages\CreateEmployee::route('/create'),
            'view'      => Pages\ViewEmployee::route('/{record}'),
            'edit'      => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
