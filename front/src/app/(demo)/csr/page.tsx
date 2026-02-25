import React from "react";
import { TodoListCsr } from "@/features/todo/components/todo-list-csr";

export default function CsrPage(props: PageProps<"/csr">) {
  return (
    <div>
      <TodoListCsr />
    </div>
  );
}
