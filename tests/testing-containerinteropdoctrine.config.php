<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use SlmQueue\Strategy\MaxRunsStrategy;
use SlmQueueDoctrine\Factory\DoctrineQueueFactory;

return [
    'slm_queue' => [
        'worker_strategies' => [
            'default' => [
                MaxRunsStrategy::class => ['max_runs' => 1]
            ]
        ],
        'queues'            => [
            'my-doctrine-queue' => [
                'deleted_lifetime' => -1,
                'buried_lifetime'  => -1,
            ],
        ],
        'queue_manager'     => [
            'factories' => [
                'newsletter' => DoctrineQueueFactory::class
            ]
        ]
    ],
    'doctrine'  => [
        'connection' => [
            'orm_default' => [
                'driverClass' => Driver::class,
                'params'      => [
                    'url' => 'sqlite:///:memory:',
                ],
            ],
        ],
        'driver' => [
            'orm_default' => [
                'class' => \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain::class,
                'drivers' => [],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'doctrine.connection.orm_default' => \ContainerInteropDoctrine\ConnectionFactory::class,
            'doctrine.entitymanager.orm_default' => \ContainerInteropDoctrine\EntityManagerFactory::class,
        ],
    ],
];
