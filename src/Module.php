<?php
/**
 * Copyright (c) 2020 Ali Cherry
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace AliChry\Laminas\BuildDelegator;

use AliChry\Laminas\BuildDelegator\Factory\BuildDelegator;
use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;

class Module implements BootstrapListenerInterface
{
    const CONFIG_ROOT_KEY = 'alichry';
    const CONFIG_MODULE_KEY = 'build_delegator';
    const CONFIG_KEYS_KEY = 'keys';
    /**
     * @param EventInterface $e
     * @throws BuildDelegatorException
     */
    public function onBootstrap(EventInterface $e)
    {
        if (! $e instanceof MvcEvent) {
            throw new BuildDelegatorException(
                sprintf(
                    'Expecting event to be an instance of %s, got %s',
                    MvcEvent::class,
                    is_object($e) ? get_class($e) : gettype($e)
                )
            );
        }
        $serviceManager = $e->getApplication()->getServiceManager();
        $this->registerBuildDelegators($serviceManager);
    }

    /**
     * @param ServiceManager $serviceManager
     * @throws BuildDelegatorException
     */
    private function registerBuildDelegators(ServiceManager $serviceManager)
    {
        $appConfig = $serviceManager->get('Config');
        $config = $appConfig[self::CONFIG_ROOT_KEY][self::CONFIG_MODULE_KEY] ?? [];

        $keys = $config[self::CONFIG_KEYS_KEY] ?? [];
        $factories = [];
        foreach ($keys as $key) {
            $services = BuildDelegator::traverseConfig($appConfig, $key);
            foreach ($services as $service => $value) {
                $factories[$key . '.' . $service] = BuildDelegator::class;
            }
        }
        $serviceManager->configure(
            [
                'factories' => $factories
            ]
        );
    }
}