<?php

namespace Livewire\Finder {
    class Finder {}
}

namespace Livewire {
    class LivewireManager
    {
        public function addNamespace(string $namespace, ?string $viewPath = null, ?string $classNamespace = null, ?string $classPath = null, ?string $classViewPath = null): void {}
    }
}

namespace Livewire\Features\SupportConsoleCommands\Commands {
    use Illuminate\Console\Command;

    class MakeCommand extends Command
    {
        /**
         * @return int
         */
        public function handle()
        {
            return 0;
        }

        protected function normalizeComponentName(string $name): string
        {
            return $name;
        }

        protected function createClassBasedComponent(string $name): int
        {
            return 0;
        }

        protected function extractViewNamespace(string $viewPath): string
        {
            return $viewPath;
        }

        protected function getClassBasedComponentTestPath(string $name): string
        {
            return $name;
        }
    }
}
