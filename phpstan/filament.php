<?php

namespace Filament {
    class Panel
    {
        public function getId(): string
        {
            return '';
        }

        public function discoverResources(string $in, string $for): static
        {
            return $this;
        }

        public function discoverPages(string $in, string $for): static
        {
            return $this;
        }

        public function discoverWidgets(string $in, string $for): static
        {
            return $this;
        }

        public function discoverClusters(string $in, string $for): static
        {
            return $this;
        }
    }
}

namespace Filament\Contracts {
    use Filament\Panel;

    interface Plugin
    {
        public function getId(): string;

        public function register(Panel $panel): void;

        public function boot(Panel $panel): void;
    }
}

namespace Filament\Support\Facades {
    use Illuminate\Support\Facades\Facade;

    /**
     * @method static void registerComponentLocation(string $path, string $namespace, ?string $viewNamespace)
     */
    class FilamentCli extends Facade {}
}
