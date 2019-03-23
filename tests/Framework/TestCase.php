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

namespace SlmQueueDoctrineTest\Framework;

use PHPUnit_Framework_TestCase;
use Doctrine\ORM\EntityManager;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var boolean
     */
    protected $hasDb = false;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Creates a database if not done already.
     */
    public function createDb()
    {
        if ($this->hasDb) {
            return;
        }

        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $conn->executeQuery(file_get_contents(__DIR__ . '/../Asset/queue_default.sqlite'));

        $this->hasDb = true;
    }

    public function dropDb()
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $conn->executeQuery('DROP TABLE queue_default');

        $this->hasDb = false;
    }

    /**
     * Get EntityManager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if ($this->entityManager) {
            return $this->entityManager;
        }

        $serviceManager = ServiceManagerFactory::getServiceManager();
        $this->entityManager = $serviceManager->get('doctrine.entity_manager.orm_default');

        return $this->entityManager;
    }
}
