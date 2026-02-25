"use client";

import type { Todo } from "@/schema";
import { TodoList } from "./todo-list";

export type TodoListSsrProps = {
  todos: Todo[];
};

export function TodoListSsr({ todos }: TodoListSsrProps) {
  return (
    <div>
      <h2 className="bg-slate-400">Todo List SSR</h2>
      <TodoList todos={todos} />
    </div>
  );
}
