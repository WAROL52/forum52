"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { useAppField } from "@/forms/hooks/use-app-fields";
import { orpc } from "@/lib/orpc";
import { createTodoSchema, type Todo } from "@/schema";

export type TodoListProps = {
  todos: Todo[];
};

export function TodoList({ todos }: TodoListProps) {
  const queryClient = useQueryClient();
  const mutation = useMutation(orpc.todo.create.mutationOptions());
  const form = useForm({
    resolver: zodResolver(createTodoSchema),
    disabled: mutation.isPending,
    defaultValues: {
      completed: false,
    },
  });

  const { Input, AppForm } = useAppField({
    form,
    async onSubmit(data) {
      queryClient.setQueryData(orpc.todo.list.queryKey(), (old = []) => {
        return [...old, { ...data, id: Date.now() }];
      });
      await mutation.mutateAsync(data);
      queryClient.invalidateQueries({
        queryKey: orpc.todo.list.queryKey(),
      });
      form.reset();
    },
  });

  return (
    <div className="max-w-2xl mx-auto ">
      <AppForm>
        <Input name="task" label="Task" placeholder="Enter your task" />
      </AppForm>
      <div className="container mx-auto mt-4">
        <ul className="flex flex-col gap-4 mx-auto ">
          {todos.map((todo) => (
            <li
              key={todo.id}
              className="flex justify-between p-3 border rounded-full"
            >
              <h3>{todo.task}</h3>
              <p>{todo.completed ? "Completed" : "Not Completed"}</p>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
