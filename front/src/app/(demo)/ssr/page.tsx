import React from "react";
import { TodoListSsr } from "@/features/todo/components/todo-list-ssr";
import { client } from "@/lib/orpc";

export default async function SsrPage(props: PageProps<"/ssr">) {
  const todos = await client.todo.list();
  return (
    <div>
      <TodoListSsr todos={todos} />
    </div>
  );
}
