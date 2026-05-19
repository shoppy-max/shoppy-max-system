@if(session('created_login'))
    @php($login = session('created_login'))
    @push('scripts')
        <script>
            window.addEventListener('load', () => {
                if (typeof window.Swal === 'undefined') {
                    return;
                }

                const login = @json($login);
                const loginText = [
                    `Login URL: ${login.login_url}`,
                    `Email: ${login.email}`,
                    `Password: ${login.password}`,
                    `Role: ${login.role}`,
                ].join('\n');
                const isDarkMode = () => document.documentElement.classList.contains('dark');
                const escapeHtml = (value) => String(value || '-')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
                const detailRows = [
                    ['Login URL', login.login_url],
                    ['Email', login.email],
                    ['Password', login.password],
                    ['Role', login.role],
                ];
                const detailsHtml = detailRows.map(([label, value]) => `
                    <div class="grid gap-1 sm:grid-cols-[120px_1fr]">
                        <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">${escapeHtml(label)}</dt>
                        <dd class="break-all ${label === 'Password' ? 'font-mono ' : ''}font-semibold text-gray-950 dark:text-white">${escapeHtml(value)}</dd>
                    </div>
                `).join('');
                const popupHtml = `
                    <div class="space-y-4 text-left">
                        <p class="text-sm text-gray-600 dark:text-gray-300">${escapeHtml(login.message || 'Share these details with the reseller. The password is shown only once.')}</p>
                        <dl class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-800">
                            ${detailsHtml}
                        </dl>
                    </div>
                `;

                window.Swal.fire({
                    title: login.headline || 'Login details ready',
                    html: popupHtml,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Copy login details',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#047857',
                    cancelButtonColor: '#6b7280',
                    background: isDarkMode() ? '#1f2937' : '#fff',
                    color: isDarkMode() ? '#fff' : '#1f2937',
                    preConfirm: () => navigator.clipboard.writeText(loginText),
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Login details copied',
                            showConfirmButton: false,
                            timer: 2200,
                            timerProgressBar: true,
                            background: isDarkMode() ? '#1f2937' : '#fff',
                            color: isDarkMode() ? '#fff' : '#1f2937',
                        });
                    }
                });
            });
        </script>
    @endpush
@endif
