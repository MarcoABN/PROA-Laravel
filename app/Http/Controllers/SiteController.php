<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use App\Models\Servico;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SiteController extends Controller
{
    public function index()
    {
        $servicos = Servico::where('ativo', true)->get();
        $artigos = Artigo::publicados()->latest('publicado_em')->take(3)->get();

        return view('site.index', compact('servicos', 'artigos'));
    }

    public function servico(Servico $servico)
    {
        abort_unless($servico->ativo, 404);

        $servicos = Servico::where('ativo', true)->get();

        return view('site.servico', compact('servico', 'servicos'));
    }

    public function blog()
    {
        $servicos = Servico::where('ativo', true)->get();
        $artigos = Artigo::publicados()->latest('publicado_em')->paginate(12);

        return view('site.blog', compact('servicos', 'artigos'));
    }

    public function artigo(Artigo $artigo)
    {
        abort_unless($artigo->estaPublicado(), 404);

        $servicos = Servico::where('ativo', true)->get();
        $outros = Artigo::publicados()->whereKeyNot($artigo->id)->latest('publicado_em')->take(3)->get();

        return view('site.artigo', compact('artigo', 'servicos', 'outros'));
    }

    public function sitemap()
    {
        $servicos = Servico::where('ativo', true)->get();
        $artigos = Artigo::publicados()->latest('publicado_em')->get();

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

        if ($artigos->isNotEmpty()) {
            $sitemap->add(
                Url::create(route('site.blog'))
                    ->setPriority(0.6)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setLastModificationDate($artigos->max('updated_at') ?? now())
            );

            foreach ($artigos as $artigo) {
                $sitemap->add(
                    Url::create(route('site.artigo', $artigo))
                        ->setPriority(0.7)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setLastModificationDate($artigo->updated_at ?? $artigo->publicado_em)
                );
            }
        }

        return $sitemap->toResponse(request());
    }
}
