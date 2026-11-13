@extends('layouts.app')

@section('title', 'Dashboard Administrateur | ERESriskAlert')

@section('content')

<!-- ===== Toastr ===== -->
<link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>

@php
    $totalUsers   = $users->count();
    $totalAdmins  = $users->where('role', 'admin')->count();
    $deptNames    = $departments->pluck('name');
    $byDept       = $users->groupBy('department');
    $assigned     = $users->filter(fn ($u) => filled($u->department))->count();
    $unassigned   = $totalUsers - $assigned;
    $orphans      = $users->filter(fn ($u) => filled($u->department) && !$deptNames->contains($u->department))
                          ->pluck('department')->unique()->values();

    $initials = fn ($u) => mb_strtoupper(mb_substr($u->firstname ?? '', 0, 1) . mb_substr($u->lastname ?? '', 0, 1));
@endphp

<div class="container mx-auto mt-8 mb-12 px-2">

    <!-- ===== En-tête ===== -->
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6 bg-white p-4 rounded shadow">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Admin</h1>
            <p class="text-sm text-gray-500">Gestion des utilisateurs et des départements · ERESriskAlert</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                Quitter
            </button>
        </form>
    </div>

    <!-- ===== Indicateurs ===== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="kpi kpi-green">
            <span class="kpi-label">Utilisateurs</span>
            <span class="kpi-value">{{ $totalUsers }}</span>
        </div>
        <div class="kpi kpi-blue">
            <span class="kpi-label">Administrateurs</span>
            <span class="kpi-value">{{ $totalAdmins }}</span>
        </div>
        <div class="kpi kpi-purple">
            <span class="kpi-label">Départements</span>
            <span class="kpi-value">{{ $departments->count() }}</span>
        </div>
        <div class="kpi kpi-yellow">
            <span class="kpi-label">Sans département</span>
            <span class="kpi-value">{{ $unassigned }}</span>
        </div>
    </div>

    <!-- ===== Onglets ===== -->
    <div class="flex gap-1 border-b border-gray-300 mb-6">
        <button type="button" data-tab="users" class="tab-btn tab-active">
            👥 Utilisateurs
            <span class="tab-count">{{ $totalUsers }}</span>
        </button>
        <button type="button" data-tab="departments" class="tab-btn">
            🏢 Départements
            <span class="tab-count">{{ $departments->count() }}</span>
        </button>
    </div>

    <!-- ================================================================= -->
    <!-- ===== PANNEAU UTILISATEURS ===================================== -->
    <!-- ================================================================= -->
    <div id="panel-users" class="tab-panel">
        <div class="bg-white p-6 rounded shadow">

            <!-- Barre d'outils -->
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <input type="search" id="userSearch" placeholder="Rechercher un nom ou un email..."
                    class="border px-3 py-2 rounded flex-1 min-w-[12rem]">

                <select id="filterRole" class="border px-3 py-2 rounded">
                    <option value="">Tous les rôles</option>
                    <option value="admin">Administrateurs</option>
                    <option value="user">Utilisateurs</option>
                </select>

                <select id="filterDept" class="border px-3 py-2 rounded">
                    <option value="">Tous les départements</option>
                    <option value="__none__">Sans département</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                    @endforeach
                </select>

                <select id="pageSize" class="border px-3 py-2 rounded" title="Lignes par page">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>

                <button type="button" id="resetFilters" class="bg-gray-200 px-3 py-2 rounded hover:bg-gray-300">
                    Réinitialiser
                </button>
            </div>

            <p id="userResultCount" class="text-sm text-gray-500 mb-2"></p>

            <!-- Tableau -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2 w-16">N°</th>
                            <th class="border px-4 py-2 text-left">Utilisateur</th>
                            <th class="border px-4 py-2">Rôle</th>
                            <th class="border px-4 py-2">Département</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        @foreach($users as $user)
                            <tr class="user-row"
                                data-search="{{ \Illuminate\Support\Str::lower($user->firstname . ' ' . $user->lastname . ' ' . $user->email) }}"
                                data-role="{{ $user->role }}"
                                data-dept="{{ $user->department }}">
                                <td class="border px-4 py-2 text-center">{{ $user->id }}</td>
                                <td class="border px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <span class="avatar">{{ $initials($user) }}</span>
                                        <div>
                                            <div class="font-semibold text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <span class="badge {{ $user->role === 'admin' ? 'badge-green' : 'badge-gray' }}">
                                        {{ $user->role === 'admin' ? 'Administrateur' : 'Utilisateur' }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    @if(filled($user->department))
                                        <span class="badge badge-purple">{{ $user->department }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2 text-center space-x-2 whitespace-nowrap">
                                    <button class="text-blue-600 editUserBtn" data-id="{{ $user->id }}">Modifier</button>
                                    <button class="text-red-600 deleteUserBtn" data-id="{{ $user->id }}">Supprimer</button>
                                </td>
                            </tr>
                        @endforeach

                        <tr id="noUserResult" style="display:none">
                            <td colspan="5" class="border px-4 py-6 text-center text-gray-500">
                                Aucun utilisateur ne correspond à ces critères.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="userPagination" class="flex flex-wrap justify-center gap-1 mt-4"></div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- ===== PANNEAU DÉPARTEMENTS ===================================== -->
    <!-- ================================================================= -->
    <div id="panel-departments" class="tab-panel hidden">

        <!-- Alerte : départements utilisés mais absents de la liste -->
        @if($orphans->isNotEmpty())
            <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded p-4 mb-6 text-sm">
                <p class="font-semibold mb-2">⚠️ Certains utilisateurs ont un département absent de la liste :</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($orphans as $name)
                        <span class="inline-flex items-center gap-2 bg-white border border-yellow-300 rounded px-2 py-1">
                            {{ $name }}
                            <button type="button" class="text-green-700 font-semibold adoptDepartmentBtn"
                                data-name="{{ $name }}">+ Ajouter à la liste</button>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Ajout d'un département -->
        <div class="bg-white p-6 rounded shadow mb-6">
            <h2 class="font-bold text-gray-800 mb-3">➕ Nouveau département</h2>
            <form id="departmentForm" class="flex flex-wrap gap-2">
                <input type="text" id="departmentName" maxlength="100" autocomplete="off"
                    placeholder="Nom du département"
                    class="flex-1 min-w-[14rem] border px-3 py-2 rounded">
                <button type="submit"
                    class="bg-green-700 text-white px-5 py-2 rounded hover:bg-green-800 transition">
                    Ajouter
                </button>
            </form>
        </div>

        <!-- Cartes des départements -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

            @foreach($departments as $dept)
                @php
                    $members = $byDept->get($dept->name, collect());
                    $count   = $members->count();
                    $pct     = $totalUsers > 0 ? round($count / $totalUsers * 100) : 0;
                @endphp

                <div class="dept-card">
                    <div class="flex justify-between items-start gap-2">
                        <h3 class="font-bold text-lg text-gray-800">{{ $dept->name }}</h3>
                        <span class="badge badge-purple">{{ $count }} membre{{ $count > 1 ? 's' : '' }}</span>
                    </div>

                    <!-- Part des utilisateurs -->
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Part des utilisateurs</span>
                            <span>{{ $pct }} %</span>
                        </div>
                        <div class="bar"><div class="bar-fill" style="width: {{ $pct }}%"></div></div>
                    </div>

                    <!-- Aperçu des membres -->
                    <div class="flex items-center mt-4 min-h-[2rem]">
                        @forelse($members->take(5) as $m)
                            <span class="avatar avatar-sm -ml-0 mr-1" title="{{ $m->firstname }} {{ $m->lastname }}">{{ $initials($m) }}</span>
                        @empty
                            <span class="text-sm text-gray-400">Aucun membre</span>
                        @endforelse
                        @if($count > 5)
                            <span class="text-sm text-gray-500 ml-1">+{{ $count - 5 }}</span>
                        @endif
                    </div>

                    <p class="text-xs text-gray-400 mt-3">
                        Créé le {{ $dept->created_at ? $dept->created_at->format('d/m/Y') : '—' }}
                    </p>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3 mt-4 pt-3 border-t text-sm">
                        <button type="button" class="text-green-700 font-semibold viewDeptUsersBtn"
                            data-name="{{ $dept->name }}">Voir les utilisateurs</button>
                        <button type="button" class="text-blue-600 renameDepartmentBtn"
                            data-id="{{ $dept->id }}" data-name="{{ $dept->name }}">Renommer</button>

                        @if($count === 0)
                            <button type="button" class="text-red-600 deleteDepartmentBtn"
                                data-id="{{ $dept->id }}" data-name="{{ $dept->name }}">Supprimer</button>
                        @else
                            <span class="text-gray-400 cursor-not-allowed"
                                title="Impossible : des utilisateurs sont rattachés à ce département">Supprimer</span>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Carte : utilisateurs sans département -->
            <div class="dept-card dept-card-muted">
                <div class="flex justify-between items-start gap-2">
                    <h3 class="font-bold text-lg text-gray-700">Sans département</h3>
                    <span class="badge badge-gray">{{ $unassigned }} utilisateur{{ $unassigned > 1 ? 's' : '' }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-3">
                    Comptes qui ne sont rattachés à aucun département.
                </p>
                <div class="flex gap-3 mt-4 pt-3 border-t text-sm">
                    <button type="button" class="text-green-700 font-semibold viewDeptUsersBtn"
                        data-name="__none__">Voir les utilisateurs</button>
                </div>
            </div>
        </div>

        @if($departments->isEmpty())
            <p class="text-center text-gray-500 mt-6">Aucun département pour le moment.</p>
        @endif
    </div>

</div>

<!-- ===== Modal Utilisateur ===== -->
<div id="userModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow w-96">
        <h2 id="modalTitle" class="font-bold text-lg mb-4"></h2>

        <form id="userForm">
            @csrf
            <input type="hidden" id="userId">

            <div class="mb-3">
                <label class="font-semibold">Prénom</label>
                <input type="text" id="firstname" class="w-full border px-3 py-2 rounded">
            </div>

            <div class="mb-3">
                <label class="font-semibold">Nom</label>
                <input type="text" id="lastname" class="w-full border px-3 py-2 rounded">
            </div>

            <div class="mb-3">
                <label class="font-semibold">Email</label>
                <input type="email" id="email" class="w-full border px-3 py-2 rounded">
            </div>

            <div class="mb-3">
                <label class="font-semibold">Rôle</label>
                <select id="role" class="w-full border px-3 py-2 rounded">
                    <option value="user">Utilisateur</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="font-semibold">Département</label>
                <select id="department" class="w-full border px-3 py-2 rounded">
                    <option value="">— Aucun —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="font-semibold">Mot de passe</label>
                <input type="password" id="password" autocomplete="new-password" class="w-full border px-3 py-2 rounded">
                <small class="text-gray-500">Laisser vide pour ne pas modifier</small>
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" id="userCancel"
                    class="bg-gray-300 px-3 py-1 rounded">Annuler</button>
                <button type="submit"
                    class="bg-green-700 text-white px-3 py-1 rounded">Valider</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Modal Département (renommer) ===== -->
<div id="departmentModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow w-96">
        <h2 class="font-bold text-lg mb-1">Renommer le département</h2>
        <p class="text-xs text-gray-500 mb-4">
            Les utilisateurs rattachés prennent automatiquement le nouveau nom.
        </p>

        <form id="departmentRenameForm">
            <input type="hidden" id="renameDeptId">
            <input type="text" id="renameDeptName" maxlength="100" autocomplete="off"
                class="w-full border px-3 py-2 rounded mb-4">

            <div class="flex justify-end space-x-2">
                <button type="button" id="renameCancel" class="bg-gray-300 px-3 py-1 rounded">Annuler</button>
                <button type="submit" class="bg-green-700 text-white px-3 py-1 rounded">Valider</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Scripts ===== -->
<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    /* ================= Onglets (gardés dans l'URL : #users / #departments) ================= */
    function showTab(name) {
        if (!$('#panel-' + name).length) name = 'users';
        $('.tab-panel').addClass('hidden');
        $('#panel-' + name).removeClass('hidden');
        $('.tab-btn').removeClass('tab-active');
        $('.tab-btn[data-tab="' + name + '"]').addClass('tab-active');
    }

    $('.tab-btn').click(function () {
        const name = $(this).data('tab');
        showTab(name);
        history.replaceState(null, '', '#' + name);
    });

    showTab(location.hash.replace('#', '') || 'users');

    /* ================= Utilisateurs : recherche, filtres, pagination ================= */
    let page = 1;

    function renderPagination(totalPages) {
        const box = $('#userPagination').empty();
        if (totalPages <= 1) return;

        const btn = (label, target, disabled, active) =>
            $('<button type="button" class="page-btn"></button>')
                .text(label)
                .toggleClass('page-active', !!active)
                .prop('disabled', !!disabled)
                .data('page', target);

        box.append(btn('‹', page - 1, page === 1));
        for (let i = 1; i <= totalPages; i++) box.append(btn(i, i, false, i === page));
        box.append(btn('›', page + 1, page === totalPages));
    }

    function applyUserFilters(keepPage) {
        if (!keepPage) page = 1;

        const q = $('#userSearch').val().trim().toLowerCase();
        const role = $('#filterRole').val();
        const dept = $('#filterDept').val();
        const size = parseInt($('#pageSize').val(), 10);

        const matched = $('.user-row').filter(function () {
            const rowDept = String($(this).data('dept') || '');
            if (q && !String($(this).data('search')).includes(q)) return false;
            if (role && $(this).data('role') !== role) return false;
            if (dept === '__none__' && rowDept !== '') return false;
            if (dept && dept !== '__none__' && rowDept !== dept) return false;
            return true;
        });

        const totalPages = Math.max(1, Math.ceil(matched.length / size));
        if (page > totalPages) page = totalPages;

        const start = (page - 1) * size;
        $('.user-row').hide();
        matched.slice(start, start + size).show();

        $('#noUserResult').toggle(matched.length === 0);

        $('#userResultCount').text(
            matched.length === 0
                ? ''
                : `${matched.length} utilisateur${matched.length > 1 ? 's' : ''} · affichage ${start + 1}–${Math.min(start + size, matched.length)}`
        );

        renderPagination(totalPages);
    }

    $('#userSearch').on('input', () => applyUserFilters());
    $('#filterRole, #filterDept, #pageSize').on('change', () => applyUserFilters());

    $('#resetFilters').click(function () {
        $('#userSearch').val('');
        $('#filterRole, #filterDept').val('');
        applyUserFilters();
    });

    $('#userPagination').on('click', '.page-btn', function () {
        if (this.disabled) return;
        page = $(this).data('page');
        applyUserFilters(true);
    });

    applyUserFilters();

    /* ================= Départements : "Voir les utilisateurs" ================= */
    $('.viewDeptUsersBtn').click(function () {
        $('#userSearch').val('');
        $('#filterRole').val('');
        $('#filterDept').val($(this).data('name'));
        applyUserFilters();
        showTab('users');
        history.replaceState(null, '', '#users');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ================= Modifier utilisateur ================= */
    $('.editUserBtn').click(function () {
        const id = $(this).data('id');

        $.get(`/admin/users/${id}`)
            .done(function (user) {
                $('#modalTitle').text('Modifier Utilisateur');
                $('#userId').val(user.id);
                $('#firstname').val(user.firstname);
                $('#lastname').val(user.lastname);
                $('#email').val(user.email);
                $('#role').val(user.role);
                $('#department').val(user.department ?? '');
                $('#password').val('');
                $('#userModal').removeClass('hidden').addClass('flex');
            })
            .fail(function () {
                toastr.error('Erreur chargement utilisateur');
            });
    });

    $('#userCancel').click(function () {
        $('#userModal').addClass('hidden').removeClass('flex');
        toastr.info('Action annulée');
    });

    $('#userForm').submit(function (e) {
        e.preventDefault();

        const id = $('#userId').val();
        const url = id ? `/admin/users/${id}` : `/admin/users`;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                firstname: $('#firstname').val(),
                lastname: $('#lastname').val(),
                email: $('#email').val(),
                role: $('#role').val(),
                department: $('#department').val(),
                password: $('#password').val()
            },
            success: function () {
                toastr.success('La modification a été effectuée avec succès.');
                setTimeout(() => location.reload(), 1200);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const messages = Object.values(xhr.responseJSON.errors).flat();
                    toastr.error(messages.join('<br>'), 'Données invalides');
                } else {
                    toastr.error('Erreur lors de l’enregistrement');
                }
            }
        });
    });

    /* ================= Départements : ajout ================= */
    function addDepartment(name) {
        $.ajax({
            url: '{{ route('admin.departments.store') }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', name: name },
            success: function () {
                toastr.success('Département ajouté');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join('<br>'), 'Données invalides');
                } else {
                    toastr.error('Erreur lors de l’ajout');
                }
            }
        });
    }

    $('#departmentForm').submit(function (e) {
        e.preventDefault();
        const name = $('#departmentName').val().trim();
        if (!name) {
            toastr.error('Saisis un nom de département');
            return;
        }
        addDepartment(name);
    });

    $('.adoptDepartmentBtn').click(function () {
        addDepartment($(this).data('name'));
    });

    /* ================= Départements : renommer ================= */
    $('.renameDepartmentBtn').click(function () {
        $('#renameDeptId').val($(this).data('id'));
        $('#renameDeptName').val($(this).data('name'));
        $('#departmentModal').removeClass('hidden').addClass('flex');
        $('#renameDeptName').focus().select();
    });

    $('#renameCancel').click(function () {
        $('#departmentModal').addClass('hidden').removeClass('flex');
    });

    $('#departmentRenameForm').submit(function (e) {
        e.preventDefault();

        const id = $('#renameDeptId').val();
        const name = $('#renameDeptName').val().trim();
        if (!name) {
            toastr.error('Saisis un nom de département');
            return;
        }

        $.ajax({
            url: `/admin/departments/${id}`,
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}', name: name },
            success: function (res) {
                const n = res.updated_users || 0;
                toastr.success(n > 0
                    ? `Département renommé (${n} utilisateur${n > 1 ? 's' : ''} mis à jour)`
                    : 'Département renommé');
                setTimeout(() => location.reload(), 1200);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join('<br>'), 'Données invalides');
                } else {
                    toastr.error('Erreur lors du renommage');
                }
            }
        });
    });

    /* ================= Départements : supprimer ================= */
    $('.deleteDepartmentBtn').click(function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        if (!confirm(`Supprimer le département « ${name} » ?`)) return;

        $.ajax({
            url: `/admin/departments/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                toastr.success('Département supprimé');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Erreur suppression');
            }
        });
    });

    /* ================= Supprimer utilisateur ================= */
    $('.deleteUserBtn').click(function () {

        const id = $(this).data('id');

        toastr.warning(
            `<br>
            <button class="confirmDelete bg-red-600 text-white px-2 py-1 rounded mr-2">Oui</button>
            <button class="cancelDelete bg-gray-400 px-2 py-1 rounded">Non</button>`,
            'Confirmer la suppression',
            {
                allowHtml: true,
                closeButton: true,
                timeOut: 0,
                onShown: function () {

                    $('.confirmDelete').click(function () {
                        $.ajax({
                            url: `/admin/users/${id}`,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                toastr.success('Utilisateur supprimé');
                                setTimeout(() => location.reload(), 1200);
                            },
                            error: function () {
                                toastr.error('Erreur suppression');
                            }
                        });
                    });

                    $('.cancelDelete').click(function () {
                        toastr.clear();
                        toastr.info('Suppression annulée');
                    });
                }
            }
        );
    });

});
</script>

<style>
/* ----- Onglets ----- */
.tab-btn {
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    color: #6b7280;
    background: transparent;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: color 0.15s ease, border-color 0.15s ease;
}
.tab-btn:hover { color: #374151; }
.tab-btn.tab-active { color: #166534; border-bottom-color: #166534; }
.tab-count {
    display: inline-block;
    min-width: 1.4rem;
    margin-left: 0.35rem;
    padding: 0 0.4rem;
    border-radius: 9999px;
    background: #e5e7eb;
    color: #374151;
    font-size: 0.75rem;
    text-align: center;
}
.tab-btn.tab-active .tab-count { background: #dcfce7; color: #166534; }

/* ----- Indicateurs ----- */
.kpi {
    display: flex;
    flex-direction: column;
    background: #fff;
    padding: 1rem 1.25rem;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    border-left: 4px solid #9ca3af;
}
.kpi-label { font-size: 0.8rem; color: #6b7280; }
.kpi-value { font-size: 1.75rem; font-weight: 700; color: #1f2937; line-height: 1.2; }
.kpi-green  { border-left-color: #15803d; }
.kpi-blue   { border-left-color: #2563eb; }
.kpi-purple { border-left-color: #7c3aed; }
.kpi-yellow { border-left-color: #eab308; }

/* ----- Avatars & badges ----- */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    background: #dcfce7;
    color: #166534;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}
.avatar-sm { width: 1.9rem; height: 1.9rem; font-size: 0.7rem; background: #ede9fe; color: #5b21b6; }

.badge {
    display: inline-block;
    padding: 0.15rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.badge-green  { background: #dcfce7; color: #166534; }
.badge-gray   { background: #e5e7eb; color: #374151; }
.badge-purple { background: #ede9fe; color: #5b21b6; }

/* ----- Cartes départements ----- */
.dept-card {
    background: #fff;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    padding: 1.25rem;
    border-top: 4px solid #0e59e4;
}
.dept-card-muted { border-top-color: #9ca3af; background: #f9fafb; }
.bar { height: 0.5rem; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
.bar-fill { height: 100%; background: #0f9637; border-radius: 9999px; }

/* ----- Pagination ----- */
.page-btn {
    min-width: 2.25rem;
    padding: 0.35rem 0.6rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background: #fff;
    color: #374151;
    cursor: pointer;
}
.page-btn:hover:not(:disabled) { background: #f3f4f6; }
.page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.page-btn.page-active { background: #166534; border-color: #166534; color: #fff; }
</style>

@endsection