import { MutationCache, QueryCache, QueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { serializer } from "../serializer";

export function createQueryClient() {
  const queryClient = new QueryClient({
    queryCache: new QueryCache({
      onError: (error) => {
        toast.error(
          "code" in error ? errorCodeFr(String(error.code)) : "Error",
          {
            description: (
              <pre className="overflow-auto">{getErrorMessage(error)}</pre>
            ),
            action: {
              label: "retry",
              onClick: () => {
                queryClient.invalidateQueries();
              },
            },
          },
        );
      },
    }),
    mutationCache: new MutationCache({
      onError: (error) => {
        toast.error(
          "code" in error ? errorCodeFr(String(error.code)) : "Error",
          {
            description: (
              <pre className="overflow-auto">{getErrorMessage(error)}</pre>
            ),
          },
        );
      },
    }),
    defaultOptions: {
      queries: {
        queryKeyHashFn(queryKey) {
          const [json, meta] = serializer.serialize(queryKey);
          return JSON.stringify({ json, meta });
        },
        staleTime: 60 * 1000, // > 0 to prevent immediate refetching on mount
      },
      // dehydrate: {
      //   shouldDehydrateQuery: (query) =>
      //     defaultShouldDehydrateQuery(query) ||
      //     query.state.status === "pending",
      //   serializeData(data) {
      //     const [json, meta] = serializer.serialize(data);
      //     return { json, meta };
      //   },
      // },
      // hydrate: {
      //   deserializeData(data) {
      //     return serializer.deserialize(data.json, data.meta);
      //   },
      // },
    },
  });
  return queryClient;
}

function getErrorMessage(error: unknown): string {
  function getError() {
    if (error instanceof Error) {
      return JSON.stringify(error.message, null, 2);
    }
    if (error && typeof error === "object" && "message" in error) {
      return JSON.stringify((error as { message: unknown }).message, null, 2);
    }
    if (error && typeof error === "object" && "error" in error) {
      return JSON.stringify((error as { error: unknown }).error, null, 2);
    }
    return JSON.stringify(error, null, 2);
  }
  return getError().replace(
    "Internal server error",
    "Erreur interne du serveur",
  );
}
const CODE_STATUS_FR: Record<string, string> = {
  BAD_REQUEST: "Requête incorrecte",
  UNAUTHORIZED: "Non autorisé",
  FORBIDDEN: "Interdit",
  NOT_FOUND: "Introuvable",
  CONFLICT: "Conflit",
  INTERNAL_SERVER_ERROR: "Erreur interne du serveur",
  SERVICE_UNAVAILABLE: "Service indisponible",
};
function errorCodeFr(code: string) {
  return CODE_STATUS_FR[code] ?? "Erreur";
}
