import z from "zod";

export const todoSchema = z.object({
  task: z.string().min(5, { message: "Task cannot be empty" }),
  completed: z.boolean(),
  id: z.number(),
});

export const createTodoSchema = todoSchema.omit({ id: true });

export const updateTodoSchema = z.object({
  id: z.number(),
  data: todoSchema.partial().omit({ id: true }),
});

export const deleteTodoSchema = z.object({
  id: z.number(),
});

export type Todo = z.infer<typeof todoSchema>;
export type CreateTodo = z.infer<typeof createTodoSchema>;
export type UpdateTodo = z.infer<typeof updateTodoSchema>;
export type DeleteTodo = z.infer<typeof deleteTodoSchema>;
