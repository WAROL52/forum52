import { TodoListHydratation } from "@/features/todo/components/todo-list-hydratation";
import { orpc } from "@/lib/orpc";
import { getQueryClient, HydrateClient } from "@/lib/query/hydration";

export default async function HydratationPage(
  props: PageProps<"/hydratation">,
) {
  const queryClient = getQueryClient();
  queryClient.prefetchQuery(orpc.todo.list.queryOptions());

  return (
    <div>
      <HydrateClient client={queryClient}>
        <TodoListHydratation />
      </HydrateClient>
    </div>
  );
}
