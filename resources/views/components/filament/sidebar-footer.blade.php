@php($user = auth()->user())
@if ($user)
    <div class="dal-sidebar-footer">
        <span class="dal-sidebar-footer__dot"></span>
        <span>{{ $user->role->getLabel() }}</span>
    </div>
@endif
