<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

/**
 * Directed acyclic dependency graph over planned tasks.
 */
final class ExecutionGraph
{
    /**
     * @param  list<PlannedTask>  $tasks
     * @param  array<string, list<string>>  $edges  taskId => prerequisite task ids
     */
    public function __construct(
        private readonly array $tasks,
        private readonly array $edges,
    ) {}

    /**
     * @param  list<PlannedTask>  $tasks
     */
    public static function fromTasks(array $tasks): self
    {
        $edges = [];
        foreach ($tasks as $task) {
            $edges[$task->id()->toString()] = $task->dependsOnTaskIds();
        }

        return new self($tasks, $edges);
    }

    /**
     * @return list<PlannedTask>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return array<string, list<string>>
     */
    public function edges(): array
    {
        return $this->edges;
    }

    /**
     * Topological order (reads/prerequisites first). Empty if cycle detected.
     *
     * @return list<PlannedTask>|null
     */
    public function topologicalOrder(): ?array
    {
        $indegree = [];
        $byId = [];
        foreach ($this->tasks as $task) {
            $id = $task->id()->toString();
            $byId[$id] = $task;
            $indegree[$id] = 0;
        }
        foreach ($this->edges as $id => $deps) {
            foreach ($deps as $dep) {
                if (isset($indegree[$id])) {
                    $indegree[$id]++;
                }
            }
        }

        $queue = [];
        foreach ($indegree as $id => $deg) {
            if ($deg === 0) {
                $queue[] = $id;
            }
        }

        $ordered = [];
        while ($queue !== []) {
            $id = array_shift($queue);
            $ordered[] = $byId[$id];
            foreach ($this->edges as $to => $deps) {
                if (in_array($id, $deps, true)) {
                    $indegree[$to]--;
                    if ($indegree[$to] === 0) {
                        $queue[] = $to;
                    }
                }
            }
        }

        if (count($ordered) !== count($this->tasks)) {
            return null;
        }

        return $ordered;
    }

    public function hasCycle(): bool
    {
        return $this->topologicalOrder() === null;
    }
}
