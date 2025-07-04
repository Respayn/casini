<div>
    <h1>{{ isset($form->id) ? 'Редактировать пользователя' : 'Создать пользователя' }}</h1>
    @include('livewire.system-settings.users.user-form')
</div>
