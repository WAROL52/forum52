"use client";

import type { InfiniteData } from "@tanstack/react-query";
import {
  type ComponentProps,
  useCallback,
  useEffect,
  useMemo,
  useRef,
} from "react";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";

export type InfiniteSelectProps = Omit<
  ComponentProps<typeof Select>,
  "children"
> & {
  // Data from useInfiniteQuery
  data: InfiniteData<any> | undefined;
  isLoading: boolean;
  isFetchingNextPage: boolean;
  hasNextPage: boolean;
  fetchNextPage: () => void;
  error: Error | null;

  // Option transformation
  mapOption: (item: any) => { label: string; value: string };

  // Optional customization
  skeletonCount?: number;
  emptyMessage?: string;
  errorMessage?: string;
  placeholder?: string;
  disabled?: boolean;
  contentClassName?: string;
};

export function InfiniteSelect({
  data,
  isLoading,
  isFetchingNextPage,
  hasNextPage,
  fetchNextPage,
  error,
  mapOption,
  skeletonCount = 3,
  emptyMessage,
  errorMessage,
  placeholder,
  disabled,
  contentClassName,
  ...selectProps
}: InfiniteSelectProps) {
  // Refs for Intersection Observer and fetch guard
  const observerRef = useRef<IntersectionObserver | null>(null);
  const isFetchingRef = useRef(false);

  // Flatten pages into options array
  const options = useMemo(() => {
    if (!data?.pages) return [];
    return data.pages.flatMap((page) =>
      page.items.map((item: unknown) => mapOption(item)),
    );
  }, [data, mapOption]);

  // Reset fetch guard when fetching completes
  useEffect(() => {
    if (!isFetchingNextPage) {
      isFetchingRef.current = false;
    }
  }, [isFetchingNextPage]);

  // Sentinel ref callback with Intersection Observer
  const sentinelRef = useCallback(
    (node: HTMLDivElement | null) => {
      // Cleanup previous observer
      if (observerRef.current) {
        observerRef.current.disconnect();
      }

      // Don't observe if no node, no more pages, or currently fetching
      if (!node || !hasNextPage || isFetchingNextPage) return;

      // Find the scrollable container parent
      const findScrollableParent = (
        element: HTMLElement,
      ): HTMLElement | null => {
        let parent = element.parentElement;

        while (parent) {
          const overflowY = window.getComputedStyle(parent).overflowY;
          const isScrollable = overflowY === "auto" || overflowY === "scroll";

          if (isScrollable) {
            // Additionally check if the element has scrollable height
            const hasScrollableHeight =
              parent.scrollHeight > parent.clientHeight;
            if (hasScrollableHeight) {
              return parent;
            }
          }

          // Stop at Radix Select Content (has data-slot attribute)
          if (parent.getAttribute("data-slot") === "select-content") {
            return parent;
          }

          // Stop at portal boundary or body
          if (
            parent.tagName === "BODY" ||
            parent === document.documentElement
          ) {
            break;
          }

          parent = parent.parentElement;
        }

        return null;
      };

      const scrollContainer = findScrollableParent(node);

      // Fallback to document viewport if no scrollable parent found
      const observerRoot = scrollContainer || null;

      const observer = new IntersectionObserver(
        ([entry]) => {
          if (entry.isIntersecting && hasNextPage && !isFetchingNextPage) {
            // Guard against duplicate calls
            if (isFetchingRef.current) return;
            isFetchingRef.current = true;
            fetchNextPage();
          }
        },
        {
          root: observerRoot,
          rootMargin: scrollContainer ? "50px" : "100px",
          threshold: 0.1,
        },
      );

      observer.observe(node);
      observerRef.current = observer;
    },
    [hasNextPage, isFetchingNextPage, fetchNextPage],
  );

  // Cleanup observer on unmount
  useEffect(() => {
    return () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
      }
    };
  }, []);

  return (
    <Select {...selectProps}>
      <SelectTrigger disabled={isLoading || disabled}>
        <SelectValue placeholder={isLoading ? "Chargement..." : placeholder} />
      </SelectTrigger>

      <SelectContent className={contentClassName}>
        <SelectGroup>
          {/* Initial loading skeletons */}
          {isLoading && (
            <>
              {Array.from({ length: skeletonCount }).map((_, i) => (
                <Skeleton
                  key={`initial-skeleton-${i}`}
                  className="mx-2 my-1 h-8 w-full"
                />
              ))}
            </>
          )}

          {/* Empty state */}
          {!isLoading && options.length === 0 && (
            <div className="px-2 py-6 text-center text-muted-foreground text-sm">
              {emptyMessage || "Aucun élément trouvé"}
            </div>
          )}

          {/* Options */}
          {!isLoading &&
            options.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}

          {/* Loading next page skeletons */}
          {isFetchingNextPage && (
            <>
              {Array.from({ length: skeletonCount }).map((_, i) => (
                <Skeleton
                  key={`next-skeleton-${i}`}
                  className="mx-2 my-1 h-8 w-full"
                />
              ))}
            </>
          )}

          {/* Error message */}
          {error && (
            <div className="px-2 py-1.5 text-destructive text-xs">
              {errorMessage || "Erreur lors du chargement"}
            </div>
          )}

          {/* Sentinel for infinite scroll */}
          {hasNextPage && !isFetchingNextPage && (
            <div ref={sentinelRef} className="h-1 w-full" />
          )}
        </SelectGroup>
      </SelectContent>
    </Select>
  );
}
