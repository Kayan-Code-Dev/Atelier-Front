<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline\Stages;

use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContextBuilder;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalBag;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalStage;
use DressnMore\Aos\Knowledge\Domain\Pipeline\KnowledgeRetrievalStageInterface;
use DressnMore\Aos\Knowledge\Domain\Policies\KnowledgePolicyEngine;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

final class PolicyFilterAndContextStage implements KnowledgeRetrievalStageInterface
{
    public function __construct(
        private readonly KnowledgePolicyEngine $policies,
        private readonly KnowledgeContextBuilder $contextBuilder,
    ) {}

    public function name(): KnowledgeRetrievalStage
    {
        return KnowledgeRetrievalStage::KnowledgeContextReady;
    }

    public function process(KnowledgeRetrievalBag $bag): void
    {
        $req = $bag->request();
        $filtered = [];
        foreach ($bag->ranked() as $hit) {
            $doc = $hit->document();
            if (! $this->policies->canRetrieve($doc, $req->tenantId(), $req->ownerId())) {
                $bag->addPolicyNote('rejected:'.$doc->id()->toString());
                continue;
            }
            // Explicit tenant isolation: never return another tenant's non-global docs.
            if ($doc->tenantId() !== null && $req->tenantId() !== null && $doc->tenantId() !== $req->tenantId()) {
                $bag->addPolicyNote('tenant_isolation:'.$doc->id()->toString());
                continue;
            }
            if ($doc->tenantId() !== null && $req->tenantId() === null && ! $req->includeGlobal()) {
                continue;
            }
            $filtered[] = $hit;
        }

        $bag->setFiltered($filtered);
        $bag->mark(KnowledgeRetrievalStage::PolicyFiltering->value);
        $bag->mark(KnowledgeRetrievalStage::TenantIsolation->value);

        $limited = array_slice($filtered, 0, $req->limit());
        $context = $this->contextBuilder->build($req->tenantId(), $req->query(), $limited, true);
        $bag->setContext($context);
        $bag->mark(KnowledgeRetrievalStage::KnowledgeCompression->value);
    }
}
