'use client'

import React, {useEffect, useState} from 'react';
import axios from "@/lib/axios";
import { Spinner } from '@/components/ui/spinner';
import InfiniteScroll from '@/components/ui/infinite-scroll';
import { Loader2 } from 'lucide-react';

const CauseListing: React.FC = () => {
    const [causes, setCauses] = useState<any>(null);
    const [isReady, setIsReady] = useState(false);
    const [page, setPage] = React.useState(0);
    const [perPage, setPerPage] = React.useState(5);
    const [loading, setLoading] = React.useState(false);
    const [hasMore, setHasMore] = React.useState(true);

    useEffect(() => {
        const fetchCauses = async () => {
            try {
                const { data } = await axios.get("causes");
                setCauses(data?.data);
                setIsReady(true);
            } catch (error) {
                console.error("Failed to fetch Ziggy routes:", error);
            }
        };

        fetchCauses();
    }, []);


    const next = async () => {
        setLoading(true);

        /**
         * Intentionally delay the search by 800ms before execution so that you can see the loading spinner.
         * In your app, you can remove this setTimeout.
         **/
        setTimeout(async () => {
            const { data } = await axios.get(`causes?page=${page}&perPage=${perPage}`);
            setCauses((prev) => [...prev, ...data.data]);
            setPage((prev) => prev + 1);
            setIsReady(true)

            // Usually your response will tell you if there is no more data.
            if (! data.pagination.hasMore) {
                setHasMore(false);
            }
            setLoading(false);
        }, 800);
    };

  return ( isReady ?
    <section className="py-12">
      <div className="container mx-auto px-6 lg:px-8">
        <h2 className="text-3xl font-bold text-center mb-8">Our Causes</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {causes.map((cause: CauseType
          ) => (
            <CauseCard key={cause.id} cause={cause} />
          ))}
            <InfiniteScroll hasMore={hasMore} isLoading={loading} next={next} threshold={1}>
                {hasMore && <Loader2 className="my-4 h-8 w-8 animate-spin" />}
            </InfiniteScroll>
        </div>
      </div>
    </section> : <Spinner size={'large'} />
  );
};

const CauseCard: React.FC<{ cause: CauseType }> = ({ cause }) => (
  <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <img src={cause.media_url} alt={cause.title} className="w-full h-48 object-cover" />
    <div className="p-4">
      <h3 className="text-xl font-semibold mb-2">{cause.title}</h3>
      <p className="text-sm text-gray-600 mb-4">{cause.description}</p>
      <div className="relative pt-1">
        <div className="overflow-hidden h-2 mb-4 text-xs flex rounded bg-emerald-200">
          <div className="flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-600" style={{ width: `${cause.progress}%` }}></div>
        </div>
        <p className="text-gray-600 text-sm">Progress: {cause.progress}%</p>
      </div>
      <a href={`/causes/${cause.id}`} className="text-indigo-600 font-semibold hover:underline">Donate Now</a>
    </div>
  </div>
);

export default CauseListing;
