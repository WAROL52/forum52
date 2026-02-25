// import { getUsersProfile } from "@/generated/api/users/users";
import "server-only";
import type { User } from "@/schema";

export type UserSessionFresh = {
  user: User;
  token: string;
};
export async function fetchUserSession(
  token: string,
): Promise<UserSessionFresh | null> {
  const headers = {
    Authorization: `Bearer ${token}`,
  };
  // const me = await getUsersProfile({ headers });
  console.error(
    "fetchUserSession is not implemented. Please implement API call to fetch user session.",
  );
  return null;
}
