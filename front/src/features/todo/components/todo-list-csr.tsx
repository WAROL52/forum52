"use client";

import { useQuery } from "@tanstack/react-query";
import { orpc } from "@/lib/orpc";
import { TodoList } from "./todo-list";

export type TodoListCsrProps = {};

export function TodoListCsr({}: TodoListCsrProps) {
  const {
    data: todos = [],
    isPending,
    isRefetching,
  } = useQuery(orpc.todo.list.queryOptions());

  if (isPending) {
    return (
      <div className="bg-green-500">
        TODO:: Loading Client Side Rendering...
      </div>
    );
  }
  return (
    <div>
      <h2 className="bg-cyan-400">
        Todo List CSR{" "}
        <span>
          isRefetching:{" "}
          {isRefetching ? (
            <span className="bg-green-500">true</span>
          ) : (
            <span className="bg-amber-600">false</span>
          )}
        </span>{" "}
      </h2>
      <TodoList todos={todos} />
    </div>
  );
}
