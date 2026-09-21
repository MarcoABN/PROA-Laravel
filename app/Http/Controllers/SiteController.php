<?php

namespace App\Http\Controllers;

use App\Models\Servico;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SiteController extends Controller
{
    public function index()
    {
        $servicos = Servico::where('ativo', true)->get();

        return view('site.index', compact('servicos'));
    }

    public function servico(Servico $servico)
    {
        abort_unless($servico->ativo, 404);

        $servicos = Servico::where('ativo', true)->get();

        return view('site.servico', compact('servico', 'servicos'));
    }

    public function sitemap()
    {
        $servicos = Servico::where('ativo', true)->get();

        $sitemap = Sitemap::create()->add(
            Url::create(route('site.index'))
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setLastModificationDate($servicos->max('updated_at') ?? now())
        );

        foreach ($servicos as $servico) {
            $url = Url::create(route('site.servico', $servico))
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY);

            if ($servico->updated_at) {
                $url->setLastModificationDate($servico->updated_at);
            }

            $sitemap->add($url);
        }

        return $sitemap->toResponse(request());
    }
}
