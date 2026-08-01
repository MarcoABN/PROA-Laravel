@auth
    {{-- A chave é versionada: ao publicar novidades, basta alterá-la para que o
         aviso reapareça mesmo para quem já está com a sessão aberta. --}}
    @if (!session()->has('aviso_sistema_lido_2026_08'))

        @php
            session()->put('aviso_sistema_lido_2026_08', true);
        @endphp

        {{--
            Estilos próprios em vez de utilitários Tailwind: o painel do Filament carrega
            apenas o CSS que ele mesmo compilou, e boa parte das classes utilitárias
            (shadow-2xl, backdrop-blur-sm, bg-*-50, tracking-wider...) não existe lá.
            As variáveis --primary-*, --gray-* e --success-* são injetadas pelo Filament
            no <head> como triplas RGB, então são seguras de usar.
        --}}
        <style>
            [x-cloak] { display: none !important; }

            .proa-aviso { position: fixed; inset: 0; z-index: 99999; display: flex;
                align-items: center; justify-content: center; padding: 1rem; }

            .proa-aviso__overlay { position: absolute; inset: 0;
                background-color: rgba(var(--gray-950), 0.7); backdrop-filter: blur(4px); }

            .proa-aviso__card { position: relative; display: flex; flex-direction: column;
                width: 100%; max-width: 40rem; max-height: 88vh; overflow: hidden;
                border-radius: 1rem; background-color: #fff;
                box-shadow: 0 25px 50px -12px rgba(var(--gray-950), 0.35),
                            0 0 0 1px rgba(var(--gray-950), 0.06); }
            .dark .proa-aviso__card { background-color: rgb(var(--gray-900));
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6),
                            0 0 0 1px rgba(255, 255, 255, 0.08); }

            .proa-aviso__head { display: flex; align-items: flex-start; gap: 1rem;
                padding: 1.25rem 1.5rem; border-bottom: 1px solid rgb(var(--gray-200)); }
            .dark .proa-aviso__head { border-bottom-color: rgba(255, 255, 255, 0.1); }

            .proa-aviso__icon { display: flex; align-items: center; justify-content: center;
                flex-shrink: 0; width: 2.75rem; height: 2.75rem; border-radius: 0.75rem;
                background-color: rgba(var(--primary-600), 0.1); color: rgb(var(--primary-600));
                box-shadow: inset 0 0 0 1px rgba(var(--primary-600), 0.2); }
            .dark .proa-aviso__icon { color: rgb(var(--primary-400)); }

            .proa-aviso__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }

            .proa-aviso__chip { display: inline-flex; align-items: center; border-radius: 0.375rem;
                padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500; line-height: 1.25rem;
                color: rgb(var(--primary-700)); background-color: rgba(var(--primary-600), 0.1);
                box-shadow: inset 0 0 0 1px rgba(var(--primary-600), 0.2); }
            .dark .proa-aviso__chip { color: rgb(var(--primary-300)); }

            .proa-aviso__date { font-size: 0.75rem; color: rgb(var(--gray-500)); }
            .dark .proa-aviso__date { color: rgb(var(--gray-400)); }

            .proa-aviso__title { margin-top: 0.375rem; font-size: 1.125rem; font-weight: 600;
                line-height: 1.5rem; color: rgb(var(--gray-950)); }
            .dark .proa-aviso__title { color: #fff; }

            .proa-aviso__close { flex-shrink: 0; display: inline-flex; align-items: center;
                justify-content: center; width: 2.25rem; height: 2.25rem; margin: -0.375rem -0.375rem 0 0;
                border: 0; border-radius: 0.5rem; background: transparent; cursor: pointer;
                color: rgb(var(--gray-400)); transition: background-color .15s, color .15s; }
            .proa-aviso__close:hover { background-color: rgb(var(--gray-100)); color: rgb(var(--gray-600)); }
            .dark .proa-aviso__close:hover { background-color: rgba(255, 255, 255, 0.06); color: rgb(var(--gray-300)); }

            .proa-aviso__body { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; }

            .proa-aviso__label { margin: 0; font-size: 0.75rem; font-weight: 600;
                text-transform: uppercase; letter-spacing: 0.05em; color: rgb(var(--gray-500)); }
            .dark .proa-aviso__label { color: rgb(var(--gray-400)); }

            .proa-aviso__list { margin: 0.75rem 0 0; padding: 0; list-style: none;
                display: flex; flex-direction: column; gap: 0.875rem; }
            .proa-aviso__item { display: flex; gap: 0.75rem; font-size: 0.875rem; line-height: 1.6;
                color: rgb(var(--gray-700)); }
            .dark .proa-aviso__item { color: rgb(var(--gray-300)); }
            .proa-aviso__item strong { font-weight: 600; color: rgb(var(--gray-950)); }
            .dark .proa-aviso__item strong { color: #fff; }

            .proa-aviso__check { flex-shrink: 0; display: flex; align-items: center;
                justify-content: center; width: 1.25rem; height: 1.25rem; margin-top: 0.125rem;
                border-radius: 9999px; color: rgb(var(--success-600));
                background-color: rgba(var(--success-600), 0.12); }
            .dark .proa-aviso__check { color: rgb(var(--success-400)); }

            .proa-aviso__panel { margin-top: 1.5rem; padding: 1rem; border-radius: 0.75rem;
                background-color: rgb(var(--gray-50)); border: 1px solid rgb(var(--gray-200)); }
            .dark .proa-aviso__panel { background-color: rgba(255, 255, 255, 0.04);
                border-color: rgba(255, 255, 255, 0.1); }

            .proa-aviso__panel-head { display: flex; align-items: center; gap: 0.5rem; }
            .proa-aviso__panel-text { margin: 0.625rem 0 0; font-size: 0.875rem; color: rgb(var(--gray-600)); }
            .dark .proa-aviso__panel-text { color: rgb(var(--gray-400)); }

            .proa-aviso__norms { margin: 0.75rem 0 0; display: flex; flex-direction: column; gap: 0.5rem; }
            .proa-aviso__norm { display: flex; flex-wrap: wrap; align-items: baseline;
                gap: 0.125rem 0.5rem; font-size: 0.875rem; color: rgb(var(--gray-700)); }
            .dark .proa-aviso__norm { color: rgb(var(--gray-300)); }
            .proa-aviso__norm code { font-size: 0.75rem; font-weight: 600;
                color: rgb(var(--primary-700)); background: transparent; padding: 0; }
            .dark .proa-aviso__norm code { color: rgb(var(--primary-400)); }

            .proa-aviso__note { margin: 0.75rem 0 0; font-size: 0.75rem; line-height: 1.6;
                color: rgb(var(--gray-500)); }
            .dark .proa-aviso__note { color: rgb(var(--gray-400)); }

            .proa-aviso__foot { display: flex; flex-wrap: wrap; align-items: center;
                justify-content: space-between; gap: 0.75rem; padding: 1rem 1.5rem;
                border-top: 1px solid rgb(var(--gray-200)); background-color: rgb(var(--gray-50)); }
            .dark .proa-aviso__foot { border-top-color: rgba(255, 255, 255, 0.1);
                background-color: rgba(255, 255, 255, 0.04); }

            .proa-aviso__btn { flex: 0 0 auto; margin-left: auto; display: inline-flex;
                align-items: center; justify-content: center; border: 0; border-radius: 0.5rem;
                cursor: pointer; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 600;
                color: #fff; background-color: rgb(var(--primary-600)); transition: background-color .15s; }
            .proa-aviso__btn:hover { background-color: rgb(var(--primary-500)); }
            .proa-aviso__btn:focus-visible { outline: 2px solid rgb(var(--primary-600));
                outline-offset: 2px; }
        </style>

        <div
            x-data="{
                showAviso: false,
                timer: null,
                open() { this.showAviso = true },
                close() {
                    this.showAviso = false
                    if (this.timer) clearTimeout(this.timer)
                }
            }"
            x-init="timer = setTimeout(() => open(), 2000)"
            x-cloak
        >
            <div
                x-show="showAviso"
                class="proa-aviso"
                @keydown.escape.window="close()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="aviso-title"
                aria-describedby="aviso-desc"
                x-trap.inert.noscroll="showAviso"
            >
                <div
                    x-show="showAviso"
                    class="proa-aviso__overlay"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="close()"
                    aria-hidden="true"
                ></div>

                <div
                    x-show="showAviso"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="proa-aviso__card"
                    @click.stop
                >
                    {{-- Cabeçalho --}}
                    <div class="proa-aviso__head">
                        <div class="proa-aviso__icon">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                            </svg>
                        </div>

                        <div style="min-width:0; flex:1;">
                            <div class="proa-aviso__meta">
                                <span class="proa-aviso__chip">Atualização do sistema</span>
                                <span class="proa-aviso__date">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
                            </div>
                            <h3 id="aviso-title" class="proa-aviso__title">Novidades e melhorias no PROA</h3>
                        </div>

                        <button type="button" class="proa-aviso__close" aria-label="Fechar aviso" @click="close()">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Corpo --}}
                    <div id="aviso-desc" class="proa-aviso__body">
                        <p class="proa-aviso__label">Nesta atualização</p>

                        <ul class="proa-aviso__list">
                            <li class="proa-aviso__item">
                                <span class="proa-aviso__check">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span>
                                    <strong>Instrutores / Procuradores</strong> agora aceitam <strong>CNPJ além de CPF</strong>,
                                    permitindo cadastrar empresas como procuradoras. Os campos exclusivos de pessoa física
                                    são ocultados automaticamente, e a procuração passa a usar a redação correta para pessoa jurídica.
                                </span>
                            </li>

                            <li class="proa-aviso__item">
                                <span class="proa-aviso__check">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span>
                                    <strong>Propostas de Serviço</strong> não apresentam mais erro quando o desconto fica
                                    em branco — o campo vazio passa a ser considerado <strong>zero</strong>.
                                </span>
                            </li>

                            <li class="proa-aviso__item">
                                <span class="proa-aviso__check">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span>
                                    <strong>Central de Anexos</strong> ganhou o documento <strong>Pedido de Informação</strong>,
                                    em Documentos Administrativos. O número do protocolo é informado na hora da emissão
                                    e a data é preenchida automaticamente.
                                </span>
                            </li>
                        </ul>

                        {{-- Conformidade normativa --}}
                        <div class="proa-aviso__panel">
                            <div class="proa-aviso__panel-head">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="color:rgb(var(--gray-500));" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p class="proa-aviso__label">Conformidade normativa</p>
                            </div>

                            <p class="proa-aviso__panel-text">
                                O sistema está em conformidade com as recentes determinações da DPC:
                            </p>

                            <div class="proa-aviso__norms">
                                <div class="proa-aviso__norm">
                                    <code>NORMAM-211/DPC</code>
                                    <span>Portaria 197 — Esporte e Recreio e Arrais.</span>
                                </div>
                                <div class="proa-aviso__norm">
                                    <code>NORMAM-212/DPC</code>
                                    <span>Portaria 198 — Moto Aquáticas e Motonautas.</span>
                                </div>
                            </div>

                            <p class="proa-aviso__note">
                                Todos os anexos gerados seguem o novo padrão oficial.
                            </p>
                        </div>
                    </div>

                    {{-- Rodapé --}}
                    <div class="proa-aviso__foot">
                        <p class="proa-aviso__note" style="margin:0;">Este aviso é exibido uma vez por sessão.</p>
                        <button type="button" class="proa-aviso__btn" @click="close()" autofocus>
                            Entendi, continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
