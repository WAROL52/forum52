import { cookies } from "next/headers";
import { decrypt } from "@/lib/jwt.server";
import { type UserSession, userSessionSchema } from "@/schema";

export async function getNoFreshUserSession(): Promise<UserSession | null> {
  const cookie = await cookies();

  const session = cookie.get("session")?.value;

  if (!session) return null;
  const parsed = await decrypt(session);
  const userSession = userSessionSchema.safeParse(parsed);
  if (!userSession.success) return null;
  return userSession.data;
}
