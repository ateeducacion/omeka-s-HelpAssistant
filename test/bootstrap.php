<?php

declare(strict_types=1);

namespace {
    // Autoload if vendor is available; tests also work with lightweight stubs below.
    @require dirname(__DIR__) . '/vendor/autoload.php';

    // Simple PSR-4 autoloader for HelpAssistant classes when composer autoload is absent.
    spl_autoload_register(function ($class): void {
        $prefix = 'HelpAssistant\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $baseDir = dirname(__DIR__);

        if ($relative === 'Module') {
            $file = $baseDir . '/Module.php';
        } else {
            $file = $baseDir . '/src/' . str_replace('\\', '/', $relative) . '.php';
        }

        if (is_readable($file)) {
            require_once $file;
        }
    });
}

namespace Laminas\EventManager {
    if (!interface_exists(SharedEventManagerInterface::class)) {
        interface SharedEventManagerInterface
        {
            public function attach($identifier, $event, $listener, $priority = 1);
        }
    }

    if (!class_exists(Event::class)) {
        class Event
        {
            private $target;

            public function __construct($target = null)
            {
                $this->target = $target;
            }

            public function getTarget()
            {
                return $this->target;
            }
        }
    }
}

namespace Laminas\View\Renderer {
    if (!class_exists(PhpRenderer::class)) {
        class PhpRenderer
        {
            private $routeMatch;
            private $headLink;
            private $headScript;

            public function __construct(array $routeMatch = [])
            {
                $this->routeMatch = $routeMatch;
            }

            public function headLink()
            {
                if (!$this->headLink) {
                    $this->headLink = new class {
                        public $stylesheets = [];
                        public function appendStylesheet($href): void
                        {
                            $this->stylesheets[] = $href;
                        }
                    };
                }

                return $this->headLink;
            }

            public function headScript()
            {
                if (!$this->headScript) {
                    $this->headScript = new class {
                        public $files = [];
                        public $inline = [];
                        public function appendFile($src): void
                        {
                            $this->files[] = $src;
                        }
                        public function appendScript($script): void
                        {
                            $this->inline[] = $script;
                        }
                    };
                }

                return $this->headScript;
            }

            public function getHelperPluginManager()
            {
                $routeMatch = $this->routeMatch;
                return new class($routeMatch) {
                    private $routeMatch;

                    public function __construct(array $routeMatch)
                    {
                        $this->routeMatch = $routeMatch;
                    }

                    public function get($name)
                    {
                        if ($name === 'params') {
                            $route = $this->routeMatch;
                            return new class($route) {
                                private $route;
                                public function __construct(array $route)
                                {
                                    $this->route = $route;
                                }
                                public function fromRoute(): array
                                {
                                    return $this->route;
                                }
                            };
                        }
                        return null;
                    }
                };
            }

            public function assetUrl(string $asset, string $module = ''): string
            {
                return ($module ? $module . '/' : '') . ltrim($asset, '/');
            }
        }
    }
}

namespace Laminas\Form {
    if (!interface_exists(InputFilterProviderInterface::class)) {
        interface InputFilterProviderInterface
        {
            public function getInputFilterSpecification();
        }
    }

    if (!class_exists(Element::class)) {
        class Element
        {
            protected $name;
            protected $options;
            protected $attributes;

            public function __construct($name = null, array $options = [], array $attributes = [])
            {
                $this->name = $name;
                $this->options = $options;
                $this->attributes = $attributes;
            }

            public function getName()
            {
                return $this->name;
            }

            public function getOptions(): array
            {
                return $this->options;
            }

            public function getOption(string $name)
            {
                return $this->options[$name] ?? null;
            }

            public function setOption(string $name, $value): void
            {
                $this->options[$name] = $value;
            }

            public function getAttributes(): array
            {
                return $this->attributes;
            }
        }
    }

    if (!class_exists(Fieldset::class)) {
        class Fieldset
        {
            protected $elements = [];

            public function add(array $spec): void
            {
                $type = $spec['type'] ?? Element::class;
                $name = $spec['name'] ?? null;
                $options = $spec['options'] ?? [];
                $attributes = $spec['attributes'] ?? [];
                $this->elements[$name] = new $type($name, $options, $attributes);
            }

            public function has(string $name): bool
            {
                return isset($this->elements[$name]);
            }

            public function get(string $name)
            {
                return $this->elements[$name] ?? null;
            }

            public function getElements(): array
            {
                return $this->elements;
            }
        }
    }

    if (!class_exists(Form::class)) {
        class Form extends Fieldset
        {
            protected $data = [];

            public function setData(array $data): void
            {
                $this->data = $data;
            }

            public function getData(): array
            {
                return $this->data;
            }

            public function prepare(): void
            {
                // No-op for stub
            }

            public function isValid(): bool
            {
                return true;
            }
        }
    }
}

namespace Laminas\Form\Element {
    if (!class_exists(Collection::class)) {
        class Collection extends \Laminas\Form\Element
        {
        }
    }

    if (!class_exists(Select::class)) {
        class Select extends \Laminas\Form\Element
        {
            public function getValueOptions(): array
            {
                return $this->options['value_options'] ?? [];
            }
        }
    }

    if (!class_exists(Textarea::class)) {
        class Textarea extends \Laminas\Form\Element
        {
        }
    }
}

namespace Laminas\Mvc\Controller {
    if (!class_exists(AbstractController::class)) {
        abstract class AbstractController
        {
            private $event;
            private $request;

            public function setEvent($event): void
            {
                $this->event = $event;
            }

            public function getEvent()
            {
                return $this->event;
            }

            public function setRequest($request): void
            {
                $this->request = $request;
            }

            public function getRequest()
            {
                return $this->request;
            }
        }
    }
}

namespace Laminas\ServiceManager\Factory {
    if (!class_exists(InvokableFactory::class)) {
        class InvokableFactory
        {
        }
    }
}

namespace Omeka\Module {
    if (!class_exists(AbstractModule::class)) {
        abstract class AbstractModule
        {
            private $services;

            public function setServiceLocator($services): void
            {
                $this->services = $services;
            }

            public function getServiceLocator()
            {
                return $this->services;
            }
        }
    }
}

namespace Omeka\Settings {
    if (!class_exists(Settings::class)) {
        class Settings
        {
            private $data = [];

            public function set(string $key, $value): void
            {
                $this->data[$key] = $value;
            }

            public function get(string $key, $default = null)
            {
                return $this->data[$key] ?? $default;
            }
        }
    }
}
