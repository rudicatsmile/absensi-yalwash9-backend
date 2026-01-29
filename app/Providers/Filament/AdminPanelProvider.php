<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup; // tambah import ini

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->registration()
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('Al-Wathoniyah Ashodriyah 9')
            ->brandLogo(asset('icon.png'))
            ->darkModeBrandLogo(asset('icon.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('icon.png'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\DashboardStatsWidget::class,
                \App\Filament\Widgets\DepartmentStatsWidget::class,
                \App\Filament\Widgets\AttendanceChartWidget::class,
                \App\Filament\Widgets\LatestAttendanceWidget::class,
                \App\Filament\Widgets\PendingApprovalsWidget::class,
                \App\Filament\Widgets\PendingOvertimeWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Data'),
                NavigationGroup::make('Settings'),
                NavigationGroup::make('Management Rapat'),
                NavigationGroup::make('Laporan'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn(): string => '
                    <style>
                        #location-consent-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;display:none;}
                        #location-consent-overlay .card{max-width:560px;margin:10vh auto;background:#fff;border-radius:.75rem;box-shadow:0 10px 25px rgba(0,0,0,.25);overflow:hidden}
                        #location-consent-overlay .header{padding:1rem 1.5rem;border-bottom:1px solid #eee;font-weight:600}
                        #location-consent-overlay .body{padding:1rem 1.5rem;color:#374151}
                        #location-consent-overlay .footer{padding:1rem 1.5rem;display:flex;gap:.75rem;align-items:center;justify-content:flex-end;background:#fafafa;border-top:1px solid #eee}
                        #location-consent-overlay .btn{padding:.5rem .9rem;border-radius:.5rem;font-weight:600;cursor:pointer;border:1px solid transparent}
                        #location-consent-overlay .btn-primary{background:#2563eb;color:#fff}
                        #location-consent-overlay .btn-secondary{background:#fff;color:#111827;border-color:#e5e7eb}
                        #location-consent-overlay .remember{display:flex;align-items:center;gap:.5rem;color:#6b7280;margin-right:auto}
                        html.location-overlay-active{overflow:hidden}
                        /* Hide default filter header for tables with this class */
                        .hide-filter-header .fi-ta-filters-header { display: none !important; }
                    </style>
                '
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn(): string => '
                    <div id="location-consent-overlay" aria-modal="true" role="dialog">
                        <div class="card" role="document" aria-labelledby="loc-consent-title" aria-describedby="loc-consent-desc">
                            <div class="header" id="loc-consent-title">Izin Akses Lokasi</div>
                            <div class="body" id="loc-consent-desc">
                                <p style="margin-bottom:.75rem">Aplikasi membutuhkan akses lokasi untuk mencatat titik koordinat saat absensi. Data lokasi hanya dipakai untuk keperluan pencatatan kehadiran pada tanggal dan waktu absensi.</p>
                                <ul style="margin-left:1rem;list-style:disc;color:#4b5563">
                                    <li>Lokasi digunakan untuk memvalidasi kehadiran sesuai kebijakan perusahaan.</li>
                                    <li>Koordinat disimpan bersama waktu absensi, tidak dibagikan ke pihak ketiga.</li>
                                    <li>Anda dapat menolak, namun beberapa fitur tidak akan berfungsi.</li>
                                </ul>
                            </div>
                            <div class="footer">
                                <label class="remember">
                                    <input type="checkbox" id="remember-consent" />
                                    Ingat pilihan saya
                                </label>
                                <button type="button" class="btn btn-secondary" data-action="deny">Not Allow</button>
                                <button type="button" class="btn btn-primary" data-action="allow">Allow</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        (function(){
                            try{
                                var PATH_TARGETS = [/\\/admin\\/manual-attendances\\b/, /\\/admin\\/manual-attendance\\b/];
                                var path = (window.location && window.location.pathname) || "";
                                var onTarget = PATH_TARGETS.some(function(r){ return r.test(path); });
                                if(!onTarget){ return; }
                                var key = "locationConsent";
                                var overlay = document.getElementById("location-consent-overlay");
                                var stored = null;
                                try { stored = localStorage.getItem(key); } catch(e) {}
                                if(stored === "allow" || stored === "deny"){
                                    return;
                                }
                                if(overlay){
                                    overlay.style.display = "block";
                                    document.documentElement.classList.add("location-overlay-active");
                                    var allowBtn = overlay.querySelector("[data-action=\\"allow\\"]");
                                    var denyBtn = overlay.querySelector("[data-action=\\"deny\\"]");
                                    var remember = overlay.querySelector("#remember-consent");
                                    function close(){
                                        overlay.style.display = "none";
                                        document.documentElement.classList.remove("location-overlay-active");
                                    }
                                    function store(keyName, val){
                                        try { localStorage.setItem(keyName, val); } catch(e) {}
                                    }
                                    function storeLatLon(v){
                                        try { localStorage.setItem("lastLatLon", v); } catch(e) {}
                                    }
                                    if(allowBtn){
                                        allowBtn.addEventListener("click", function(){
                                            if(remember && remember.checked){ store(key, "allow"); }
                                            close();
                                            if(navigator.geolocation){
                                                navigator.geolocation.getCurrentPosition(function(p){
                                                    var v = p.coords.latitude + "," + p.coords.longitude;
                                                    storeLatLon(v);
                                                    try { window.dispatchEvent(new CustomEvent("location-consent:granted", { detail: { latlon: v } })); } catch(e) {}
                                                }, function(err){
                                                    try { window.dispatchEvent(new CustomEvent("location-consent:error", { detail: { code: err && err.code, message: err && err.message } })); } catch(e) {}
                                                }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 });
                                            }
                                        });
                                    }
                                    if(denyBtn){
                                        denyBtn.addEventListener("click", function(){
                                            if(remember && remember.checked){ store(key, "deny"); }
                                            close();
                                            try { window.dispatchEvent(new CustomEvent("location-consent:denied")); } catch(e) {}
                                        });
                                    }
                                }
                            }catch(e){}
                        })();
                    </script>
                '
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): string => app()->environment(['local', 'development']) ?
                '<script>
                        function fillLoginForm() {
                            const emailInput = document.querySelector(\'input[type="email"]\');
                            const passwordInput = document.querySelector(\'input[type="password"]\');

                            if (emailInput && emailInput.value === \'\') {
                                emailInput.value = \'admin@admin.com\';
                                emailInput.dispatchEvent(new Event(\'input\', { bubbles: true }));
                                emailInput.dispatchEvent(new Event(\'change\', { bubbles: true }));
                            }

                            if (passwordInput && passwordInput.value === \'\') {
                                passwordInput.value = \'password\';
                                passwordInput.dispatchEvent(new Event(\'input\', { bubbles: true }));
                                passwordInput.dispatchEvent(new Event(\'change\', { bubbles: true }));
                            }
                        }

                        document.addEventListener(\'DOMContentLoaded\', function() {
                            setTimeout(fillLoginForm, 100);
                            setTimeout(fillLoginForm, 500);
                            setTimeout(fillLoginForm, 1000);
                        });

                        document.addEventListener(\'livewire:init\', function() {
                            setTimeout(fillLoginForm, 100);
                            setTimeout(fillLoginForm, 500);
                        });

                        window.addEventListener(\'load\', function() {
                            setTimeout(fillLoginForm, 100);
                        });
                    </script>' : ''
            );
    }
}
