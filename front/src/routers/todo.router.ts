import z from "zod";
import { createTodoSchema, type Todo, todoSchema } from "@/schema/todo.schema";
import { wait } from "@/utils/wait";
import { publicProcedure } from "../middlewares/public-procedure";

let todos: Todo[] = [
  { task: "Buy groceries", completed: false, id: 1 },
  { task: "Walk the dog", completed: true, id: 2 },
];

const listTodo = publicProcedure
  .output(todoSchema.array())
  .handler(async () => {
    console.log("====================");
    console.log("Listing todos:", todos.length);
    console.log("====================");
    await wait(3000);
    return todos;
  });

const createTodo = publicProcedure
  .input(createTodoSchema)
  .output(todoSchema)
  .handler(async ({ input }) => {
    const newTodo = { ...input, id: Date.now() };
    todos.push(newTodo);
    await wait(3000);
    return newTodo;
  });

const deleteTodo = publicProcedure
  .input(z.object({ id: z.number() }))
  .handler(async ({ input }) => {
    todos = todos.filter((todo) => todo.id !== input.id);
    return { success: true };
  });

const updateTodo = publicProcedure
  .input(z.object({ id: z.number(), data: todoSchema.partial() }))
  .handler(async ({ input }) => {
    todos = todos.map((todo) =>
      todo.id === input.id ? { ...todo, ...input.data } : todo,
    );
    return { success: true };
  });

export const todoRouter = {
  list: listTodo,
  create: createTodo,
  delete: deleteTodo,
  update: updateTodo,
};
