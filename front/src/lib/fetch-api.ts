import { getNoFreshUserSession } from "@/auth/utils/get-no-fresh-user-session";

// NOTE: Supports cases where `content-type` is other than `json`
const getBody = <T>(c: Response | Request): Promise<T> => {
  const contentType = c.headers.get("content-type");

  if (contentType?.includes("application/json")) {
    return c.json();
  }

  if (contentType?.includes("application/pdf")) {
    return c.blob() as Promise<T>;
  }

  return c.text() as Promise<T>;
};

// NOTE: Update just base url
const getUrl = (contextUrl: string): string => {
  const baseUrl = process.env.SERVER_URL;

  const url = new URL(`${baseUrl}${contextUrl}`);

  const pathname = url.pathname;
  const search = url.search;
  const origin = url.origin;

  const requestUrl = new URL(`${origin}${pathname}${search}`);

  return requestUrl.toString();
};

// NOTE: Add headers
const getHeaders = async (headers?: HeadersInit): Promise<HeadersInit> => {
  const userSession = await getNoFreshUserSession();
  console.log({
    userSession: userSession?.user.email || null,
    roles: userSession?.user.roles || null,
  });
  const newHeaders = {
    ...headers,
  };
  const header = newHeaders as Record<string, string>;
  if (userSession?.session.token) {
    header.Authorization = `Bearer ${userSession.session.token}`;
    if (userSession.session.businessId)
      header["Business-Id"] = userSession.session.businessId.toString();
  }
  return header;
};

export const fetchApi = async <T>(
  url: string,
  options: RequestInit,
): Promise<T> => {
  const requestUrl = getUrl(url);
  const requestHeaders = await getHeaders(options.headers);

  const requestInit: RequestInit = {
    ...options,
    headers: requestHeaders,
  };
  const token =
    "Authorization" in requestHeaders
      ? "Authorization: Bearer ****"
      : "No Authorization";

  const businessId =
    "Business-Id" in requestHeaders ? "Business-Id: ****" : "No Business-Id";
  console.log(
    `-> ${requestInit.method}:[${requestUrl}]  |${token}\t |${businessId}`,
  );

  const response = await fetch(requestUrl, requestInit);
  const data = await getBody<T>(response);
  const dataAny = data as { error?: string; message?: string };
  console.log(
    `<- ${requestInit.method}:[${requestUrl}]  |${response.status} ${JSON.stringify(dataAny.error || dataAny.message || "")}\n`,
  );

  return { status: response.status, data, headers: response.headers } as T;
};
