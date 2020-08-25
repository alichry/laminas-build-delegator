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

namespace AliChry\Laminas\BuildDelegator\Test\Factory;

use AliChry\Laminas\BuildDelegator\BuildDelegatorException;
use AliChry\Laminas\BuildDelegator\Factory\BuildDelegator as Factory;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class BuildDelegatorTest extends TestCase
{
    /**
     * @var Factory|MockObject
     */
    private $factory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $mockContainer;

    /**
     * @var ServiceManager|MockObject
     */
    private $mockServiceManager;

    /**
     * @var \array[][]
     */
    private $config = [
        'one' => [
            'two' => [
                'three' => 'service',
                'four' => [
                    'service' => 'service',
                    'options' => [
                        'option1' => 'value1'
                    ]
                ],
                'five' => [
                    'service' => 'service'
                ],
                'six' => [
                    'options' => [

                    ]
                ]
            ]
        ]
    ];

    public function setUp()
    {
        $this->factory = new Factory();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockServiceManager = $this->createMock(ServiceManager::class);
    }

    public function testInvalidKey()
    {
        $key = 'one.two.abc';
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('Config'))
            ->willReturn($this->config);
        $this->expectException(ServiceNotCreatedException::class);
        $this->invoke($key);
    }

    public function testWithoutBuildOptions()
    {
        $key = 'one.two.three';
        $service = $this->config['one']['two']['three'];
        $object = new \stdClass();
        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['Config', $this->config],
                        [$service, $object]
                    ]
                )
            );
        $this->assertSame(
            $object,
            $this->invoke($key)
        );
    }

    public function testWithBuildOptions()
    {
        $key = 'one.two.four';
        $service = $this->config['one']['two']['four'];
        $object = new \stdClass();
        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['Config', $this->config],
                        [ServiceManager::class, $this->mockServiceManager]
                    ]
                )
            );
        $this->mockServiceManager->expects($this->once())
            ->method('build')
            ->with(
                $this->identicalTo($service['service']),
                $this->identicalTo($service['options'])
            )->willReturn($object);

        $this->assertSame(
            $object,
            $this->invoke($key)
        );
    }

    public function testWithBuildOptionsAndMissingOptions()
    {
        $key = 'one.two.five';
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('Config'))
            ->willReturn($this->config);
        $this->expectException(ServiceNotCreatedException::class);
        $this->invoke($key);
    }

    public function testWithBuildOptionsAndMissingServiceName()
    {
        $key = 'one.two.six';
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('Config'))
            ->willReturn($this->config);
        $this->expectException(ServiceNotCreatedException::class);
        $this->invoke($key);
    }

    public function testTraverseConfigEmptyKey()
    {
        $this->expectException(BuildDelegatorException::class);
        $this->factory::traverseConfig([], '');
    }

    public function testTraverseConfigInvalidKey()
    {
        $this->expectException(BuildDelegatorException::class);
        $this->factory::traverseConfig(['abc' => true], 'hi');
    }

    protected function invoke($requestedName = null)
    {
        return $this->factory->__invoke(
            $this->mockContainer,
            $requestedName
        );
    }
}