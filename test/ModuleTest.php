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

declare(strict_types=1);

namespace AliChry\Laminas\BuildDelegator\Test;

use AliChry\Laminas\BuildDelegator\BuildDelegatorException;
use AliChry\Laminas\BuildDelegator\Factory\BuildDelegator;
use AliChry\Laminas\BuildDelegator\Module;
use Laminas\EventManager\EventInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var array
     */
    private $config = [
        'alichry' => [
            'build_delegator' => [
                'keys' => [
                    'alichry.access_control.list_adapter',
                    'alichry.access_control.resource_manager',
                    'alichry.access_control.list'
                ]
            ],
            'access_control' => [
                'list_adapter' => [
                    'one' => '1',
                    'two' => '2',
                    'three' => [
                        'service' => '3'
                    ]
                ],
                'resource_manager' => [
                    'four' => '4',
                    'five' => '5',
                    'six' => [
                        'service' => '6'
                    ],
                ],
                'list' => [
                    'seven' => '7',
                    'eight' => '8',
                    'nine' => [
                        'service' => '9'
                    ]
                ]
            ]
        ]
    ];

    public function setUp()
    {
        $this->module = new Module();
    }

    public function testOnBootsrapBadEvent()
    {
        $e = $this->createMock(EventInterface::class);
        $this->expectException(BuildDelegatorException::class);
        $this->module->onBootstrap($e);
    }

    public function testRegisterDeligatorsBadServiceLocator()
    {
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockApplication = $this->createMock(Application::class);
        $mockServiceManager = $this->createMock(ServiceLocatorInterface::class);
        $mockEvent->expects($this->once())
            ->method('getApplication')
            ->willReturn($mockApplication);
        $mockApplication->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($mockServiceManager);
        $this->expectException(\TypeError::class);
        $this->module->onBootstrap($mockEvent);
    }

    public function testRegisterDeligatorsMocked()
    {
        $config = $this->config;
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockApplication = $this->createMock(Application::class);
        $mockServiceManager = $this->createMock(ServiceManager::class);
        $mockEvent->expects($this->once())
            ->method('getApplication')
            ->willReturn($mockApplication);
        $mockApplication->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($mockServiceManager);
        $mockServiceManager->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('Config'))
            ->willReturn($config);
        $mockServiceManager->expects($this->once())
            ->method('configure')
            ->with($this->callback(function ($factories) use ($config) {
                $expectedFactories = [];
                $prefix = 'alichry.access_control.';
                $ac = $config['alichry']['access_control'];
                foreach ($ac as $parentKey => $next) {
                    foreach ($next as $key => $service) {
                        $expectedFactories[$prefix . $parentKey . '.' . $key] =
                            BuildDelegator::class;
                    }
                }

                $expected = ['factories' => $expectedFactories];
                return $factories === $expected;
            }));

        $this->module->onBootstrap($mockEvent);
    }

    /*
    public function testRegisterBuildDelegator()
    {
        $prefix = 'alichry.access_control.';
        $this->module->onBootstrap($this->event);
        $serviceManager = $this->event->getApplication()->getServiceManager();
        $this->assertEquals(
            new \stdClass(),
            $serviceManager->get(
                $prefix . '.' . 'one'
            )
        );
    }
    */
}