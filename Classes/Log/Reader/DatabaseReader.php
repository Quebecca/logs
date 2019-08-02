<?php
namespace VerteXVaaR\Logs\Log\Reader;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use VerteXVaaR\Logs\Domain\Model\Filter;
use VerteXVaaR\Logs\Domain\Model\Log;
use function implode;
use function json_decode;
use function strlen;
use function substr;

/**
 * Class DatabaseReader
 */
class DatabaseReader implements ReaderInterface
{
    /**
     * @var array
     */
    protected $selectFields = ['request_id', 'time_micro', 'component', 'level', 'message', 'data'];

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var \TYPO3\CMS\Core\Database\Connection|null
     */
    protected $connection = null;

    /**
     * DatabaseReader constructor.
     *
     * @param array|null $configuration
     */
    public function __construct(array $configuration = null)
    {
        if (null !== $configuration && isset($configuration['logTable'])) {
            $this->table = $configuration['logTable'];
        } else {
            $this->table = 'sys_log';
        }
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
    }

    /**
     * @param Filter $filter
     * @return Log[]
     * @throws DBALException
     */
    public function findByFilter(Filter $filter): array
    {
        $fields = $this->getSelectFieldsByFilter($filter);
        $constraints = $this->getWhereClausByFilter($filter);
        $orderField = $filter->getOrderField();
        $orderDirection = $filter->getOrderDirection();
        $limit = $filter->getLimit();
        $statement = $this->connection->query(
            <<<SQL
            SELECT {$fields}
            FROM {$this->table}
            WHERE {$constraints}
            ORDER BY {$orderField} {$orderDirection}
            LIMIT {$limit}
SQL
        );
        return $this->fetchLogsByStatement($statement);
    }

    /**
     * @param Filter $filter
     * @return string
     */
    protected function getWhereClausByFilter(Filter $filter): string
    {
        $where = [
            'level <= "' . LogLevel::normalizeLevel($filter->getLevel()) . '"',
            'message IS NOT NULL',
        ];
        $requestId = $filter->getRequestId();
        if (!empty($requestId)) {
            /* @see \TYPO3\CMS\Core\Core\Bootstrap::__construct for requestId string length */
            if (13 === strlen($requestId)) {
                $where[] = Filter::ORDER_REQUEST_ID . ' = ' . $this->quoteString($requestId);
            } else {
                $where[] = Filter::ORDER_REQUEST_ID . ' LIKE ' . $this->quoteString("%$requestId%");
            }
        }
        $fromTime = $filter->getFromTime();
        if (!empty($fromTime)) {
            $where[] = Filter::ORDER_TIME_MICRO . ' >= ' . $this->quoteString($fromTime);
        }
        $toTime = $filter->getToTime();
        if (!empty($toTime)) {
            // Add +1 to the timestamp to ignore additional microseconds when comparing. UX stuff, you know ;)
            $where[] = Filter::ORDER_TIME_MICRO . ' <= ' . $this->quoteString($toTime + 1);
        }
        $component = $filter->getComponent();
        if (!empty($component)) {
            $where[] = Filter::ORDER_COMPONENT . ' LIKE ' . $this->quoteString("%$component%");
        }
        return implode(' AND ', $where);
    }

    /**
     * @param Filter $filter
     * @return string
     */
    protected function getSelectFieldsByFilter(Filter $filter): string
    {
        $selectFields = $this->selectFields;
        $filter->isFullMessage() ?: $selectFields[4] = 'CONCAT(LEFT(message , 120), "...") as message';
        $filter->isShowData() ?: $selectFields[5] = '"- {}"';
        return implode(',', $selectFields);
    }

    /**
     * @param Statement $statement
     * @return Log[]
     */
    protected function fetchLogsByStatement(Statement $statement): array
    {
        $logs = [];

        $statement->setFetchMode(PDO::FETCH_NUM);
        if ($statement->execute()) {
            while (($row = $statement->fetch())) {
                $row[5] = json_decode(substr($row[5], 2), true);
                if (empty($row[5])) {
                    $row[5] = [];
                }
                $logs[] = GeneralUtility::makeInstance(
                    Log::class,
                    $row[0],
                    $row[1],
                    $row[2],
                    $row[3],
                    $row[4],
                    $row[5]
                );
            }
            return $logs;
        }
        return $logs;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function quoteString(string $string): string
    {
        return $this->connection->quote($string);
    }
}
