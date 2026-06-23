<?php

namespace App\Filament\Resources\GmvReports;

use App\Filament\Resources\GmvReports\Pages\CreateGmvReport;
use App\Filament\Resources\GmvReports\Pages\EditGmvReport;
use App\Filament\Resources\GmvReports\Pages\ListGmvReports;
use App\Filament\Resources\GmvReports\Schemas\GmvReportForm;
use App\Filament\Resources\GmvReports\Tables\GmvReportsTable; // <-- Ini wajib biar file Table lu kepake!
use App\Models\GmvReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;

class GmvReportResource extends Resource
{
    protected static ?string $model = GmvReport::class;

    protected static ?string $modelLabel = 'Laporan GMV';
    protected static ?string $pluralModelLabel = 'Laporan GMV';
    protected static ?string $navigationLabel = 'Laporan GMV';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        if ($record && $record->employee) {
            return "GMV {$record->employee->name}";
        }
        return 'Laporan GMV';
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('employee', 'name')
                    ->required(),
                Forms\Components\FileUpload::make('screenshot_path')
                    ->label('Foto Bukti')
                    ->disk('r2')
                    ->directory('gmv-reports'),
                Forms\Components\TextInput::make('account_name')
                    ->label('Nama Akun')
                    ->maxLength(255),
                Forms\Components\TextInput::make('platform')
                    ->label('Platform')
                    ->maxLength(255),
                Forms\Components\TimePicker::make('live_start')
                    ->label('Jam Mulai Live'),
                Forms\Components\TimePicker::make('live_end')
                    ->label('Jam Selesai Live'),
                Forms\Components\TextInput::make('gmv_amount')
                    ->label('Omset (Rp)')
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('order_count')
                    ->label('Jumlah Pesanan')
                    ->numeric(),
                Forms\Components\TextInput::make('product_sold')
                    ->label('Produk Terjual')
                    ->numeric(),
                Forms\Components\TextInput::make('viewers_count')
                    ->label('Jumlah Penonton')
                    ->numeric(),
                Forms\Components\TextInput::make('highest_viewers')
                    ->label('Penonton Tertinggi')
                    ->numeric(),
                Forms\Components\DatePicker::make('live_date')
                    ->label('Tanggal Live')
                    ->required(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('screenshot_path')
                    ->label('Foto Laporan')
                    ->disk('r2')
                    ->width(150)
                    ->height(100)
                    ->action(
                        \Filament\Actions\Action::make('view_image')
                            ->modalHeading('Screenshot GMV')
                            ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<img src="' . \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl($record->screenshot_path, now()->addMinutes(10)) . '" style="width: 100%; border-radius: 8px;" />'))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                    ),
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('live_time')
                    ->label('Jam Live')
                    ->getStateUsing(fn ($record) => $record->live_start && $record->live_end ? "{$record->live_start} - {$record->live_end}" : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gmv_amount')
                    ->label('Total GMV (Rp)')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_count')
                    ->label('Pesanan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_sold')
                    ->label('Produk Terjual')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('viewers_count')
                    ->label('Dilihat (Viewers)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('highest_viewers')
                    ->label('Penonton Tertinggi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('live_date')
                    ->label('Tanggal Live')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Lapor')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGmvReports::route('/'),
            'create' => CreateGmvReport::route('/create'),
            'edit' => EditGmvReport::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\GmvStats::class,
        ];
    }
}
