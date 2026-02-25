import { getUserSession } from "@/auth/utils/user-session.server";
import { openApiHandler } from "@/lib/open-api-handler";

async function handleRequest(request: Request) {
  if (process.env.NODE_ENV !== "development") {
    return new Response("Not found", { status: 404 });
  }
  const { response } = await openApiHandler.handle(request, {
    prefix: "/api",
    context: {
      userSession: await getUserSession(),
    },
  });

  return response ?? new Response("Not found", { status: 404 });
}

export const HEAD = handleRequest;
export const GET = handleRequest;
export const POST = handleRequest;
export const PUT = handleRequest;
export const PATCH = handleRequest;
export const DELETE = handleRequest;
