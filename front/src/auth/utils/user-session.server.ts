import "server-only";
import { cookies } from "next/headers";
import { encrypt } from "@/lib/jwt.server";
import {
  UserRole,
  type UserSession,
  userSessionSchema,
} from "@/schema/auth.schema";
import "server-only";
import { redirect } from "next/navigation";
import { fetchUserSession } from "./fetch-user-session";
import { getNoFreshUserSession } from "./get-no-fresh-user-session";

export async function getFreshUserSession(
  token: string,
): Promise<UserSession | null> {
  const data = await fetchUserSession(token);
  if (!data) return null;
  const businessId = data.user.businessId || null;
  try {
    const userSession: UserSession = {
      user: data.user,
      session: {
        createdAt: new Date(),
        expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 days
        token: data.token,
        disableSessionRefresh: false,
        refresh_token: undefined,
        freshAgeInSeconds: 60 * 60, // 1 hour
        updatedAt: new Date(),
        userId: data.user.id,
        ipAddress: "",
        userAgent: "",
        businessId,
      },
    };
    return userSessionSchema.parse(userSession);
  } catch (error) {
    console.error(error);
    return null;
  }
}

export async function getUserSession(): Promise<UserSession | null> {
  const userSession = await getNoFreshUserSession();
  if (!userSession) return null;
  if (userSession.session.expiresAt <= new Date()) {
    await setUserSession(null);
    return null;
  }
  return userSession;
}

export async function setUserSession(
  userSession: UserSession | null,
): Promise<UserSession | null> {
  const cookie = await cookies();
  if (!userSession) {
    cookie.set("session", "", { expires: new Date(0) });
    return null;
  }
  const userSessionParsed = userSessionSchema.parse(userSession);
  cookie.set("session", await encrypt(userSessionParsed), {
    httpOnly: true,
    expires: userSessionParsed.session.expiresAt,
  });
  return userSessionParsed;
}

export function isUserAdmin(userSession: UserSession) {
  return userSession.user.roles.includes(UserRole.SUPER_ADMIN);
}

export function isUserSuperAdmin(userSession: UserSession) {
  return userSession.user.roles.includes(UserRole.SUPER_ADMIN);
}

export function hasRole(
  userSession: UserSession,
  role: (typeof UserRole)[keyof typeof UserRole],
) {
  return userSession.user.roles.includes(role);
}

export async function requireUserSession() {
  const userSession = await getUserSession();
  if (!userSession) redirect("/login");
  if (userSession.user.isEmailValidated === false) {
    redirect("/confirm-account");
  }
  return userSession;
}

export async function requireUserAdminSession() {
  const userSession = await requireUserSession();
  if (!isUserAdmin(userSession)) redirect("/dashboard");
  return userSession;
}

export async function requireUserSuperAdminSession() {
  const userSession = await requireUserSession();
  if (!isUserSuperAdmin(userSession)) redirect("/dashboard");
  return userSession;
}
