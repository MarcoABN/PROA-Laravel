<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Capitania;
use Carbon\Carbon;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class Anexo3D implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 3D - Construção/Alteração'; }
    
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo3D-N211.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Select::make('capitania_id')
                ->label('Organização Militar')
                ->options(fn() => Capitania::all()->pluck('nome', 'id'))
                ->default(fn() => Capitania::where('padrao', true)->first()?->id)
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('construtor')->label('Estaleiro / Construtor')->required(),
            Radio::make('tipo_obra')
                ->options([
                    'Construída' => 'Construída',
                    'Alterada' => 'Alterada'
                ])
                ->default('Construída')
                ->required(),
        ];
    }

    public function getDados($record, array $input): array
    {
        // Este anexo é específico para embarcação, então o $record é uma Embarcacao
        $embarcacao = $record;
        
        Carbon::setLocale('pt_BR');
        $capitania = Capitania::find($input['capitania_id']);
        $nomeCapitania = $capitania ? mb_strtoupper($capitania->nome) : '';

        return [
            'orgmilitar' => $this->up($nomeCapitania),
            'nomeconstrutor' => $this->up($input['construtor']),
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao),
            
            'construida_alterada' => $this->up($input['tipo_obra']),
            'construida_alterada2'=> $this->up($input['tipo_obra']),
            
            'areanavegacao' => $this->up($embarcacao->area_navegacao),
            'comprimentototal' => $embarcacao->comp_total ? $embarcacao->comp_total . 'm' : '',
            'comprimentoperpend' => $embarcacao->comp_perpendicular ? $embarcacao->comp_perpendicular . 'm' : '',
            'bocamoldada' => $embarcacao->boca_moldada ? $embarcacao->boca_moldada . 'm' : '',
            'pontalmoldado' => $embarcacao->pontal_moldado ? $embarcacao->pontal_moldado . 'm' : '',
            
            'localdata' => $this->up(($embarcacao->cidade ?? 'Brasília')) . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
    }

    private function up($valor)
    {
        return mb_strtoupper((string)($valor ?? ''), 'UTF-8');
    }
}