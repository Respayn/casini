<?php

namespace Src\Application\Templates\Create;

use Src\Domain\Templates\Template;
use Src\Domain\Templates\TemplateRepositoryInterface;

class CreateTemplateCommandHandler
{
    private readonly TemplateRepositoryInterface $templateRepository;

    public function __construct(TemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function handle(CreateTemplateCommand $command): void
    {
        // Используем класс файла и метод Store из фреймворка для упрощения.
        // В идеале нужно создать интерфейс для абстракции от файлохранилища
        $path = $command->file->store('templates');

        $template = Template::create(
            $command->file->getClientOriginalName(),
            $path
        );

        $this->templateRepository->save($template);
    }
}