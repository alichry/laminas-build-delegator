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

namespace AliChry\Laminas\BuildDelegator\Factory;

use AliChry\Laminas\BuildDelegator\BuildDelegatorException;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;

class BuildDelegator implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        try {
            $service = self::traverseConfig($config, $requestedName);
        } catch (BuildDelegatorException $e) {
            throw new ServiceNotCreatedException(
                'Unable to traverse config',
                0,
                $e
            );
        }
        if (! is_array($service)) {
            return $container->get($service);
        }
        $name = $service['service'] ?? null;
        $options = $service['options'] ?? null;
        if (null === $name) {
            throw new ServiceNotCreatedException(
                sprintf(
                    '%s should have the key "service" defined in the array',
                    $requestedName
                )
            );
        }
        if (null === $options) {
            throw new ServiceNotCreatedException(
                sprintf(
                    '%s should have the key "options" defined in the array',
                    $requestedName
                )
            );
        }
        $serviceManager = $container->get(ServiceManager::class);
        return $serviceManager->build(
            $name,
            $options
        );
    }

    /**
     * @param $config
     * @param $key
     * @throws BuildDelegatorException
     * @return mixed
     */
    public static function traverseConfig($config, $key)
    {
        if (empty($key)) {
            throw new BuildDelegatorException(
                'Passed key is empty!'
            );
        }
        $subKeys = explode('.', $key);
        $value = $config;
        foreach ($subKeys as $subKey) {
            if (! isset($value[$subKey])) {
                throw new BuildDelegatorException(
                    sprintf(
                        'sub key "%s", part of "%s" does not exist in config',
                        $subKey,
                        $key
                    )
                );
            }
            $value = $value[$subKey];
        }
        return $value;
    }
}