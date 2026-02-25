import React from "react";

export default async function DashboardPage(props: PageProps<"/dashboard">) {
  const params = await props.params;

  return (
    <div>
      <h1>Page</h1>
      <pre>Params: {JSON.stringify(params, null, 2)}</pre>
      <footer>Footer content here</footer>
    </div>
  );
}
