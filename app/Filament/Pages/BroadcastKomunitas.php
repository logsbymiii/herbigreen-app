<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use App\Services\MessageProviderFactory;

class BroadcastKomunitas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Broadcast Komunitas';
    protected static ?string $title = 'Broadcast Komunitas Telegram';
    protected static ?string $navigationGroup = 'Komunikasi';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.broadcast-komunitas';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('pesan')
                    ->label('Pesan Broadcast')
                    ->required()
                    ->rows(5)
                    ->placeholder('Ketik pesan untuk dikirim ke grup komunitas HerbiGreen...'),
                    
                FileUpload::make('gambar')
                    ->label('Lampiran Gambar (Opsional)')
                    ->image()
                    ->directory('broadcasts'),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('Kirim Broadcast')
                ->submit('kirim')
                ->color('primary')
                ->icon('heroicon-m-paper-airplane')
        ];
    }

    public function kirim(): void
    {
        $data = $this->form->getState();

        if (!Storage::exists('community_group_id.txt')) {
            Notification::make()
                ->title('Grup Belum Di-set!')
                ->body('Pastikan Anda sudah mengundang bot ke grup dan mengetik /init_community.')
                ->danger()
                ->send();
            return;
        }

        $communityGroupId = trim(Storage::get('community_group_id.txt'));
        $provider = MessageProviderFactory::create();
        
        $pesanLengkap = "📢 *PENGUMUMAN KOMUNITAS*\n\n" . $data['pesan'];

        try {
            if (!empty($data['gambar'])) {
                // If using Telegram, we can send photo. The provider sendFile is available in Fonnte and Telegram.
                // Assuming provider->sendFile($chatId, $fileUrl, $caption)
                $fileUrl = asset('storage/' . $data['gambar']);
                
                // Temporary workaround since we don't have sendPhoto on base interface, we use sendMessage for now
                // OR we can specifically call Telegram API if it's Telegram.
                $botToken = env('TELEGRAM_BOT_TOKEN');
                if ($botToken) {
                     \Illuminate\Support\Facades\Http::attach(
                        'photo',
                        Storage::disk('public')->get($data['gambar']),
                        'broadcast.jpg'
                    )->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                        'chat_id' => $communityGroupId,
                        'caption' => $pesanLengkap,
                        'parse_mode' => 'Markdown'
                    ]);
                } else {
                    $provider->sendMessage($communityGroupId, $pesanLengkap . "\n\n(Ada lampiran gambar: {$fileUrl})");
                }
            } else {
                $provider->sendMessage($communityGroupId, $pesanLengkap);
            }

            Notification::make()
                ->title('Broadcast Terkirim!')
                ->body('Pesan berhasil dikirim ke grup komunitas.')
                ->success()
                ->send();
                
            $this->form->fill(); // Reset form

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Mengirim Broadcast')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
