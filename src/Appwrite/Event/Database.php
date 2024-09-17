<?php

namespace Appwrite\Event;

use Utopia\Database\Document;
use Utopia\DSN\DSN;
use Utopia\Queue\Client;
use Utopia\Queue\Connection;

class Database extends Event
{
    protected string $type = '';
    protected ?Document $database = null;
    protected ?Document $collection = null;
    protected ?Document $document = null;

    public function __construct(protected Connection $connection)
    {
        parent::__construct($connection);

        $this->setClass(Event::DATABASE_CLASS_NAME);
    }

    /**
     * Sets the type for this database event (use the constants starting with DATABASE_TYPE_*).
     *
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns the set type for the database event.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the database for this event
     *
     * @param Document $database
     * @return self
     */
    public function setDatabase(Document $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Set the collection for this database event.
     *
     * @param Document $collection
     * @return self
     */
    public function setCollection(Document $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Returns set collection for this event.
     *
     * @return null|Document
     */
    public function getCollection(): ?Document
    {
        return $this->collection;
    }

    /**
     * Set the document for this database event.
     *
     * @param Document $document
     * @return self
     */
    public function setDocument(Document $document): self
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Returns set document for this database event.
     * @return null|Document
     */
    public function getDocument(): ?Document
    {
        return $this->document;
    }

    /**
     * Executes the event and send it to the database worker.
     *
     * @return string|bool
     * @throws \InvalidArgumentException
     */
    public function trigger(): string|bool
    {

        var_dump('database=');
        var_dump($this->getProject()->getAttribute('database'));

        try {
            $dsn = new DSN($this->getProject()->getAttribute('database'));
        } catch (\InvalidArgumentException) {
            // TODO: Temporary until all projects are using shared tables
            $dsn = new DSN('mysql://' . $this->getProject()->getAttribute('database'));
        }

        var_dump('$dsn->getHost()=');
        var_dump($dsn->getHost());

        $this->setQueue($dsn->getHost());

        $client = new Client($this->queue, $this->connection);

        try {
            $result = $client->enqueue([
                'sourceRegion' =>  System::getEnv('_APP_REGION', 'default'),
                'project' => $this->project,
                'user' => $this->user,
                'type' => $this->type,
                'collection' => $this->collection,
                'document' => $this->document,
                'database' => $this->database,
                'events' => Event::generateEvents($this->getEvent(), $this->getParams())
            ]);
            return $result;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
