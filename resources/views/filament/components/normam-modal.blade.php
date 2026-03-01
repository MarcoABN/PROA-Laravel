@auth
    @if (!session()->has('aviso_normam_lido_sessao'))

        @php
            session()->put('aviso_normam_lido_sessao', true);
        @endphp

        <style>
            [x-cloak] { display: none !important; }
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
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="close()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="aviso-title"
                x-trap.inert.noscroll="showAviso"
            >
                {{-- Overlay com cor diferente + fade --}}
                <div
                    x-show="showAviso"
                    class="absolute inset-0 bg-indigo-950/80 backdrop-blur-sm"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="close()"
                    aria-hidden="true"
                ></div>

                {{-- Card mais impactante --}}
                <div
                    x-show="showAviso"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-12 sm:translate-y-0 sm:scale-90"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-250"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-gray-900 dark:ring-white/10"
                    @click.stop
                >
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-widest text-primary-600 dark:text-primary-400 uppercase">
                                Atualização obrigatória
                            </p>
                            <h3 id="aviso-title" class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                PROA atualizado para novas NORMAM
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                            aria-label="Fechar"
                            @click="close()"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 text-sm text-gray-600 dark:text-gray-300">
                        <p>O sistema já está em conformidade com as recentes determinações da DPC.</p>

                        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Principais ajustes
                            </p>

                            <ul class="mt-3 space-y-2">
                                <li class="flex gap-2">
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary-600"></span>
                                    <span class="text-gray-800 dark:text-gray-100">
                                        NORMAM-211/DPC - Portaria 197: Esporte e Recreio e Arrais.
                                    </span>
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary-600"></span>
                                    <span class="text-gray-800 dark:text-gray-100">
                                        NORMAM-212/DPC - Portaria 198: Moto Aquáticas e Motonautas.
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <p class="mt-4 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            Todos os anexos gerados já estão em conformidade com o novo padrão oficial.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:text-gray-200 dark:hover:bg-gray-800 sm:w-auto"
                            @click="close()"
                        >
                            Fechar
                        </button>

                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 sm:w-auto"
                            @click="close()"
                            autofocus
                        >
                            Entendi, continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
