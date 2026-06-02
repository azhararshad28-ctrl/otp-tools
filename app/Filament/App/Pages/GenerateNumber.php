<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Services\ProviderInterface;
use Filament\Notifications\Notification;
use App\Models\PhoneNumber;

class GenerateNumber extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.app.pages.generate-number';
    protected static ?string $navigationLabel = 'Generate Number';
    protected static ?string $title = 'Generate Virtual Number';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('country_id')
                    ->label('Select Country')
                    ->options(Country::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('service')
                    ->label('Service (e.g. WhatsApp, Telegram)')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'telegram' => 'Telegram',
                        'google' => 'Google / Gmail',
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'tiktok' => 'TikTok',
                        'twitter' => 'Twitter / X',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->searchable(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Number')
                ->submit('generate')
                ->color('primary')
        ];
    }

    public function generate(ProviderInterface $apiService)
    {
        $data = $this->form->getState();
        $country = Country::find($data['country_id']);

        if (!$country) {
            Notification::make()->title('Country not found')->danger()->send();
            return;
        }

        try {
            $response = $apiService->generateNumber($country->code, $data['service']);
            
            if ($response['success']) {
                PhoneNumber::create([
                    'user_id' => auth()->id(),
                    'country_id' => $country->id,
                    'phone_number' => $response['data']['phone_number'] ?? 'N/A',
                    'service' => $data['service'],
                    'provider_order_id' => $response['data']['order_id'] ?? null,
                    'status' => 'active',
                ]);
                
                Notification::make()->title('Number generated successfully!')->success()->send();
                return redirect()->to('/app/my-numbers');
            } else {
                Notification::make()->title('API Error')->body($response['message'] ?? 'Unknown error')->danger()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('System Error')->body($e->getMessage())->danger()->send();
        }
    }
}
