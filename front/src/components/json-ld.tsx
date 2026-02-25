import type { Thing, WithContext } from "schema-dts";

export type JsonLdProps<T extends Thing> = {
  jsonLd: WithContext<T>;
};
/**
 * Renders JSON-LD structured data for SEO purposes.
 *
 * for test https://search.google.com/test/rich-results
 *
 * for validation https://validator.schema.org/
 *
 */
export function JsonLd<T extends Thing>({ jsonLd }: JsonLdProps<T>) {
  const props = {
    dangerouslySetInnerHTML: {
      __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c"),
    },
  };
  return <script type="application/ld+json" {...props} />;
}
