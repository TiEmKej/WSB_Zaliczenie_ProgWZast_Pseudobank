@props(['user'])

<span @class(['badge', 'badge-admin' => $user->isAdmin()])>{{ $user->roleLabel() }}</span>
