export type TPaginationResponse<T> = {
  data: T[];
  current_page: number;
  per_page?: number;
  total: number;
  total_pages: number;
};

export type TSingleResponse<T> = {
  data: T;
  status: boolean;
  message: string;
};