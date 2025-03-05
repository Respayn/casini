<div>
    <div class="flex justify-between items-center">
        <h1 class="text-primary-text text-xl font-semibold">Клиенты и Клиенто-проекты</h1>

        <div class="flex gap-2 items-center">
            <!-- Здесь будет логика для отображения списка клиентов -->
            <a href="{{ route('system-settings.clients-and-projects.clients.create') }}" class="btn inline-flex items-center justify-center bg-primary text-white hover:bg-primary-dark rounded-lg px-4 py-2">
                + Создать клиента
            </a>
            <!-- Здесь будет логика для отображения списка клиенто-проектов -->
            <a href="{{ route('system-settings.clients-and-projects.projects.create') }}" class="btn inline-flex items-center justify-center bg-primary text-white hover:bg-primary-dark rounded-lg px-4 py-2">
                + Создать клиенто-проект
            </a>
        </div>
    </div>
</div>
