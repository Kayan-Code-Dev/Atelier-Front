import { mutationOptions, queryOptions, useQueryClient } from "@tanstack/react-query";
import {
  blockBranchManager,
  createBranchManager,
  deleteBranchManager,
  deleteBranchManagerPermanently,
  getAllDeletedBranchManagers,
  getBranchManagers,
  getBranchesManagersIds,
  restoreBranchManager,
  updateBranchManager,
} from "./branch-manager.service";
import { toast } from "sonner";
import { TBranchManager } from "./branch-manager.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const BRANCH_MANGER_KEY = "branch-managers";
export const DELETED_BRANCH_MANGERS_KEY = "deleted-branch-managers";

type TPageData = TPaginationResponse<TBranchManager>;

export const useGetBranchManagersQueryOptions = (page: number) =>
  queryOptions({
    queryKey: [BRANCH_MANGER_KEY, page],
    queryFn: () => getBranchManagers(page),
  });

export const useGetBranchesManagersIdsQueryOptions = () =>
  queryOptions({
    queryKey: ["branch-managers-ids"],
    queryFn: getBranchesManagersIds,
  });

export const useCreateBranchManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createBranchManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCH_MANGER_KEY] });
      toast.success("تم إنشاء مدير فرع جديد بنجاح");
    },
  });
};

export const useUpdateBranchManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: string; data: FormData }) =>
      updateBranchManager(id, data),
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCH_MANGER_KEY] });
      toast.success("تم تحديث مدير الفرع بنجاح");
    },
  });
};

export const useDeleteBranchManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteBranchManager,
    onMutate: async (idToDelete: string) => {
      await qClient.cancelQueries({ queryKey: [BRANCH_MANGER_KEY] });
      const previousDataSnapshot = qClient.getQueriesData<TPageData>({
        queryKey: [BRANCH_MANGER_KEY],
      });
      qClient.setQueriesData<TPageData>(
        { queryKey: [BRANCH_MANGER_KEY] },
        (oldData) => {
          if (!oldData) return;
          return {
            ...oldData,
            total: oldData.total - 1,
            data: oldData.data.filter((m) => m.uuid !== idToDelete),
          };
        },
      );
      return { previousDataSnapshot };
    },
    onError: (_, __, context) => {
      if (context?.previousDataSnapshot) {
        context.previousDataSnapshot.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      toast.error("حدث خطأ. تم التراجع عن الحذف");
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [BRANCH_MANGER_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCH_MANGERS_KEY] });
    },
    onSuccess: () => {
      toast.success("تم مسح مدير الفرع بنجاح");
    },
  });
};

export const useGetAllDeletedBranchManagersQueryOptions = (page: number) =>
  queryOptions({
    queryKey: [DELETED_BRANCH_MANGERS_KEY, page],
    queryFn: () => getAllDeletedBranchManagers(page),
  });

export const useRestoreBranchManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: restoreBranchManager,
    onMutate: async (idToRestore: string) => {
      await qClient.cancelQueries({ queryKey: [DELETED_BRANCH_MANGERS_KEY] });
      const previousDataSnapshot = qClient.getQueriesData<TPageData>({
        queryKey: [DELETED_BRANCH_MANGERS_KEY],
      });
      qClient.setQueriesData<TPageData>(
        { queryKey: [DELETED_BRANCH_MANGERS_KEY] },
        (oldData) => {
          if (!oldData) return;
          return {
            ...oldData,
            total: oldData.total - 1,
            data: oldData.data.filter((m) => m.uuid !== idToRestore),
          };
        },
      );
      return { previousDataSnapshot };
    },
    onError: (_, __, context) => {
      if (context?.previousDataSnapshot) {
        context.previousDataSnapshot.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      toast.error("حدث خطأ. تم التراجع عن الاستعادة");
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [BRANCH_MANGER_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCH_MANGERS_KEY] });
    },
    onSuccess: () => {
      toast.success("تم استعادة مدير الفرع بنجاح");
    },
  });
};

export const useDeleteBranchManagerPermanentlyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteBranchManagerPermanently,
    onMutate: async (idToDelete: string) => {
      await qClient.cancelQueries({ queryKey: [DELETED_BRANCH_MANGERS_KEY] });
      const previousDataSnapshot = qClient.getQueriesData<TPageData>({
        queryKey: [DELETED_BRANCH_MANGERS_KEY],
      });
      qClient.setQueriesData<TPageData>(
        { queryKey: [DELETED_BRANCH_MANGERS_KEY] },
        (oldData) => {
          if (!oldData) return;
          return {
            ...oldData,
            total: oldData.total - 1,
            data: oldData.data.filter((m) => m.uuid !== idToDelete),
          };
        },
      );
      return { previousDataSnapshot };
    },
    onError: (_, __, context) => {
      if (context?.previousDataSnapshot) {
        context.previousDataSnapshot.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      toast.error("حدث خطأ. تم التراجع عن الحذف النهائي");
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCH_MANGERS_KEY] });
    },
    onSuccess: () => {
      toast.success("تم مسح مدير الفرع نهائيا");
    },
  });
};

export const useBlockBranchManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: blockBranchManager,
    onMutate: async (idToBlock: string) => {
      await qClient.cancelQueries({ queryKey: [BRANCH_MANGER_KEY] });
      const previousDataSnapshot = qClient.getQueriesData<TPageData>({
        queryKey: [BRANCH_MANGER_KEY],
      });
      qClient.setQueriesData<TPageData>(
        { queryKey: [BRANCH_MANGER_KEY] },
        (oldData) => {
          if (!oldData) return;
          return {
            ...oldData,
            data: oldData.data.map((m) =>
              m.uuid === idToBlock ? { ...m, blocked: !m.blocked } : m,
            ),
          };
        },
      );
      return { previousDataSnapshot };
    },
    onError: (_, __, context) => {
      if (context?.previousDataSnapshot) {
        context.previousDataSnapshot.forEach(([queryKey, data]) => {
          qClient.setQueryData(queryKey, data);
        });
      }
      toast.error("حدث خطأ. تم التراجع عن تغيير حالة الحظر");
    },
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [BRANCH_MANGER_KEY] });
    },
    onSuccess: () => {
      toast.success("تم تغيير حالة حظر المدير بنجاح");
    },
  });
};
