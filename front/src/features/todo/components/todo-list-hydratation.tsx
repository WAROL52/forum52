"use client";

import { useSuspenseQuery } from "@tanstack/react-query";
import { orpc } from "@/lib/orpc";
import { TodoList } from "./todo-list";

export type TodoListHydratationProps = {};

export function TodoListHydratation({}: TodoListHydratationProps) {
  const query = useSuspenseQuery(orpc.todo.list.queryOptions());
  console.log(query);
  console.log("render...");

  return (
    <div>
      <h2 className="bg-amber-300">
        Todo List Hydratation{" "}
        <span>
          isLoading:{" "}
          {query.isLoading ? (
            <span className="bg-green-500">true</span>
          ) : (
            <span className="bg-amber-600">false</span>
          )}
        </span>{" "}
      </h2>
      <TodoList todos={query.data || []} />
    </div>
  );
}
