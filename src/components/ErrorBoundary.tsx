import { Component, type ErrorInfo, type ReactNode } from "react";
import { Button } from "@/components/ui/button";

type Props = {
  children: ReactNode;
  fallback?: ReactNode;
};

type State = {
  hasError: boolean;
  error: Error | null;
};

/**
 * Global Error Boundary to prevent white screen on uncaught React errors.
 * Shows a fallback UI with retry and home options.
 */
export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    if (import.meta.env.DEV) {
      console.error("ErrorBoundary caught an error:", error, errorInfo);
    }
  }

  handleRetry = (): void => {
    this.setState({ hasError: false, error: null });
  };

  handleGoHome = (): void => {
    this.setState({ hasError: false, error: null });
    window.location.href = "/dashboard";
  };

  render(): ReactNode {
    if (this.state.hasError && this.state.error) {
      if (this.props.fallback) {
        return this.props.fallback;
      }
      return (
        <div className="flex min-h-[50vh] flex-col items-center justify-center gap-4 p-8 text-center">
          <h2 className="text-xl font-semibold text-foreground">
            حدث خطأ غير متوقع
          </h2>
          <p className="max-w-md text-sm text-muted-foreground">
            نعتذر عن الإزعاج. يمكنك إعادة المحاولة أو العودة للرئيسية.
          </p>
          {import.meta.env.DEV && this.state.error && (
            <pre className="max-h-32 overflow-auto rounded bg-muted p-2 text-left text-xs">
              {this.state.error.message}
            </pre>
          )}
          <div className="flex gap-2">
            <Button onClick={this.handleRetry} variant="outline">
              إعادة المحاولة
            </Button>
            <Button onClick={this.handleGoHome}>العودة للرئيسية</Button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}
